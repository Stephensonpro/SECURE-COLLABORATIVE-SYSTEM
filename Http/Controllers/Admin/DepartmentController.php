<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request)
    {
        $departments = Department::query()
            ->with(['hod', 'staff'])
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->type, fn($query, $type) => $query->where('type', $type))
            ->when($request->status, fn($query, $status) => $query->where('status', $status))
            ->when($request->sort, function($query, $sort) {
                switch ($sort) {
                    case 'oldest': return $query->oldest();
                    case 'name': return $query->orderBy('name');
                    default: return $query->latest();
                }
            }, fn($query) => $query->latest())
            ->paginate(10);

        $staffMembers = Staff::active()->get();

        return view('admin.departments.index', compact('departments', 'staffMembers'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create()
    {
        $staffMembers = Staff::active()->get();
        return view('admin.departments.create', compact('staffMembers'));
    }

    /**
     * Store a newly created department.
     */
/**
 * Store a newly created department.
 */
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:departments',
        'code' => 'required|string|max:50|unique:departments',
        'type' => 'required|in:clinical,support,administrative',
        'hod_id' => 'nullable|exists:staff,id',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    // Set status based on checkbox
    $validated['status'] = $request->has('status') ? 'active' : 'inactive';

    Department::create($validated);

    return redirect()->route('admin.departments.index')
        ->with('success', 'Department created successfully');
}


    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        $department->load(['hod', 'staff']);
        return view('admin.departments.show', compact('department'));
    }




/**
 * Show the form for editing the department.
 */
public function edit(Department $department, Request $request)
{
    $staffMembers = Staff::active()->get();

    // Query for department staff with optional search
    $departmentStaff = $department->staff()
        ->when($request->search, function($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->paginate(10);

    // Keep existing query parameters for pagination links
    $departmentStaff->appends($request->query());

    return view('admin.departments.edit', compact('department', 'staffMembers', 'departmentStaff'));
}




    /**
     * Update the specified department.
     */
/**
 * Update the specified department.
 */
public function update(Request $request, Department $department)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:departments,name,'.$department->id,
        'code' => 'required|string|max:50|unique:departments,code,'.$department->id,
        'type' => 'required|in:clinical,support,administrative',
        'hod_id' => 'nullable|exists:staff,id',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    // Convert status to correct value
    $validated['status'] = $request->has('status') ? 'active' : 'inactive';

    $department->update($validated);

    return redirect()->route('admin.departments.index')
        ->with('success', 'Department updated successfully');
}

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        if ($department->staff()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete department with assigned staff members');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully');
    }

    /**
     * Update department status via AJAX.
     */
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ]);

        $department = Department::find($validated['id']);
        $department->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $department->status
        ]);
    }
}
