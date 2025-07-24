<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\StaffAccountCreated;
use Illuminate\Support\Facades\Mail;
use App\Imports\StaffImport;
use Maatwebsite\Excel\Facades\Excel;

class StaffController extends Controller
{


    public function index(Request $request)
    {
        $query = Staff::with('department');

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->has('type') && in_array($request->type, ['customer', 'crew'])) {
            $query->where('type', $request->type);
        }

        // Department filter
        if ($request->has('department')) {
            $query->where('department_id', $request->department);
        }





        // Sorting
        switch ($request->sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->orderBy('name');
                break;
            default: // newest
                $query->latest();
                break;
        }

        $staff = $query->paginate(12);
        $sdepartments = Department::all();
        $designations = Designation::all();


        return view('admin.staff.index', compact('staff', 'sdepartments', 'designations'));
    }




    public function create()
    {
        $departments = Department::all();
        $designations = Designation::all();
        return view('admin.staff.create', compact('departments', 'designations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'type' => 'required|in:clinical,support,administrative',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'qualification' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employment_date' => 'nullable|date',
            'marital_status' => 'nullable|string|max:20',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',

        ]);

        $password = Str::random(10);

        $staffData = array_merge($validated, [
            'password' => Hash::make($password),
        ]);

        $staff = Staff::create($staffData);

        Mail::to($staff->email)->send(new StaffAccountCreated($staff, $password));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Staff created successfully! ID: ' . $staff->staff_id,
                'redirect' => route('admin.staff.index')
            ]);
        }

        return redirect()->route('admin.staff.index')
            ->with('success', "Staff created successfully! ID: {$staff->staff_id}");
    }



    public function bulkCreate()
    {
        return view('admin.staff.bulk-create');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'staff_file' => 'required|file|mimes:csv,xlsx,xls'
        ]);

        Excel::import(new StaffImport, $request->file('staff_file'));

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff members imported successfully!');
    }




public function edit(Staff $staff)
{
    $departments = Department::all();
    $designations = Designation::all();
    return view('admin.staff.edit', compact('staff', 'departments', 'designations'));
}

public function update(Request $request, Staff $staff)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:staff,email,'.$staff->id,
        'type' => 'required|in:clinical,support,administrative',
        'department_id' => 'required|exists:departments,id',
        'designation_id' => 'nullable|exists:designations,id', // Add this line
        'phone' => 'required|string|max:20',
        'gender' => 'nullable|in:male,female,other',
        'dob' => 'nullable|date',
        'address' => 'nullable|string|max:500',
        'qualification' => 'nullable|string|max:255',
        'position' => 'nullable|string|max:255',
        'employment_date' => 'nullable|date',
        'marital_status' => 'nullable|string|max:20',
        'next_of_kin' => 'nullable|string|max:255',
        'next_of_kin_phone' => 'nullable|string|max:20',
        'bank_name' => 'nullable|string|max:100',
        'account_number' => 'nullable|string|max:20',
        'tax_id' => 'nullable|string|max:50',
    ]);

    $staff->update($validated);

    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully!',
            'redirect' => route('admin.staff.index')
        ]);
    }

    return redirect()->route('admin.staff.index')
        ->with('success', 'Staff updated successfully!');
}




  public function destroy(Staff $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted successfully!');
    }
}
