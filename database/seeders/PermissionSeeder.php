<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions with admin guard

        $permissions = [


    // Announcement Management
    ['name' => 'announcement.view', 'group' => 'announcement', 'guard_name' => 'admin'],
    ['name' => 'announcement.create', 'group' => 'announcement', 'guard_name' => 'admin'],
    ['name' => 'announcement.edit', 'group' => 'announcement', 'guard_name' => 'admin'],
    ['name' => 'announcement.delete', 'group' => 'announcement', 'guard_name' => 'admin'],

    // Message Management
    ['name' => 'messages.view', 'group' => 'messages', 'guard_name' => 'admin'],
    ['name' => 'messages.create', 'group' => 'messages', 'guard_name' => 'admin'],

    // Document Management
    ['name' => 'documents.view', 'group' => 'documents', 'guard_name' => 'admin'],
    ['name' => 'documents.create', 'group' => 'documents', 'guard_name' => 'admin'],
    ['name' => 'documents.edit', 'group' => 'documents', 'guard_name' => 'admin'],
    ['name' => 'documents.delete', 'group' => 'documents', 'guard_name' => 'admin'],
    ['name' => 'documents.download', 'group' => 'documents', 'guard_name' => 'admin'],
    ['name' => 'documents.view_logs', 'group' => 'documents', 'guard_name' => 'admin'],

    // Task Management
    ['name' => 'tasks.view', 'group' => 'tasks', 'guard_name' => 'admin'],
    ['name' => 'tasks.create', 'group' => 'tasks', 'guard_name' => 'admin'],
    ['name' => 'tasks.edit', 'group' => 'tasks', 'guard_name' => 'admin'],
    ['name' => 'tasks.delete', 'group' => 'tasks', 'guard_name' => 'admin'],

    // Audit Log
    ['name' => 'audit_logs.view', 'group' => 'audit_logs', 'guard_name' => 'admin'],
];


        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name'], 'guard_name' => $permission['guard_name']], $permission);
        }
    }
}
