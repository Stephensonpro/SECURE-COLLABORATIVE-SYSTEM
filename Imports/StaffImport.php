<?php

namespace App\Imports;

use App\Models\Staff;
use App\Models\Department;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\StaffAccountCreated;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class StaffImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $password = Str::random(10);

            // Handle department (ID or name)
            $departmentId = $this->resolveDepartment($row);

            // Format dates
            $dob = $this->parseDate($row['dob'] ?? null);
            $employmentDate = $this->parseDate($row['employment_date'] ?? null);

            $staffData = [
                'name' => $row['name'],
                'email' => $row['email'],
                'type' => strtolower($row['type'] ?? 'academic'),
                'department_id' => $departmentId,
                'phone' => $row['phone'] ?? null,
                'gender' => strtolower($row['gender'] ?? null),
                'dob' => $dob,
                'address' => $row['address'] ?? null,
                'qualification' => $row['qualification'] ?? null,
                'position' => $row['position'] ?? null,
                'employment_date' => $employmentDate,
                'marital_status' => $row['marital_status'] ?? null,
                'next_of_kin' => $row['next_of_kin'] ?? null,
                'next_of_kin_phone' => $row['next_of_kin_phone'] ?? null,
                'bank_name' => $row['bank_name'] ?? null,
                'account_number' => $row['account_number'] ?? null,
                'tax_id' => $row['tax_id'] ?? null,
                'password' => Hash::make($password),
            ];

            $staff = Staff::create($staffData);

            Mail::to($staff->email)->send(new StaffAccountCreated($staff, $password));
        }
    }

    protected function resolveDepartment($row)
    {
        if (!empty($row['department_id'])) {
            return $row['department_id'];
        }

        if (!empty($row['department'])) {
            $department = Department::where('name', $row['department'])->first();
            return $department ? $department->id : null;
        }

        return null;
    }

    protected function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
