<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\Admin;

class SyncStaffToAdmin extends Command
{
    protected $signature = 'sync:staff-to-admin';
    protected $description = 'Sync all staff records to admin table';

    public function handle()
    {
        Staff::chunk(100, function ($staffMembers) {
            foreach ($staffMembers as $staff) {
                Admin::updateOrCreate(
                    ['email' => $staff->email],
                    [
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'password' => $staff->password,
                    ]
                );
            }
        });

        $this->info('All staff records have been synced to the admin table.');
    }
}
