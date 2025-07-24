<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentialsMail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = Admin::with('roles')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins', // Changed to admins table
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id,guard_name,admin'
        ]);

        $password = Str::random(12);
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        // Assign roles
        $roles = Role::whereIn('id', $request->roles)
            ->where('guard_name', 'admin')
            ->get();

        $admin->syncRoles($roles);

        Mail::to($admin->email)->send(new UserCredentialsMail($admin->email, $password));

        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user created successfully. Login details sent via email.');
    }

    public function edit(Admin $admin) // Changed parameter type
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $adminRoles = $admin->roles->pluck('id')->toArray();
        return view('admin.users.edit', compact('admin', 'roles', 'adminRoles')); // Changed variable name
    }

    public function update(Request $request, Admin $admin) // Changed parameter type
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admins')->ignore($admin->id)], // Changed to admins table
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id,guard_name,admin'
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Assign roles
        $roles = Role::whereIn('id', $request->roles)
            ->where('guard_name', 'admin')
            ->get();

        $admin->syncRoles($roles);

        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user updated successfully');
    }

    public function destroy(Admin $admin) // Changed parameter type
    {
        if ($admin->id === auth('admin')->id()) { // Changed to admin guard
            return redirect()->back()
                ->with('error', 'You cannot delete your own account');
        }

        $admin->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'Admin user deleted successfully');
    }

    public function resetPassword(Request $request, Admin $admin) // Changed parameter type
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Password reset successfully');
    }
}
