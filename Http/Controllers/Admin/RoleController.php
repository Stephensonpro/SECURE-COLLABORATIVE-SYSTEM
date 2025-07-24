<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->where('guard_name', 'admin')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'admin')->get()->groupBy('group');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,NULL,id,guard_name,admin',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id,guard_name,admin' // Added guard_name check
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'admin'
            ]);

            // Get the permission IDs and sync them
            $permissions = Permission::whereIn('id', $request->permissions)
                ->where('guard_name', 'admin')
                ->pluck('id')
                ->toArray();

            $role->syncPermissions($permissions);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating role: ' . $e->getMessage());
        }
    }

    public function edit(Role $role)
    {
        // Ensure we're only editing admin guard roles
        if ($role->guard_name !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $permissions = Permission::where('guard_name', 'admin')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }




    public function update(Request $request, Role $role)
    {
        // Ensure we're only updating admin guard roles
        if ($role->guard_name !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)->where('guard_name', 'admin')
            ],
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id,guard_name,admin' // Added guard_name check
        ]);

        try {
            $role->update(['name' => $request->name]);

            // Get the permission IDs and sync them
            $permissions = Permission::whereIn('id', $request->permissions)
                ->where('guard_name', 'admin')
                ->pluck('id')
                ->toArray();

            $role->syncPermissions($permissions);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        // Ensure we're only deleting admin guard roles
        if ($role->guard_name !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deleting admin role
        if ($role->name === 'Super Admin') {
            return redirect()->back()
                ->with('error', 'Cannot delete Super Admin role');
        }

        try {
            $role->delete();
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }
}
