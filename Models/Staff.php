<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use App\Models\Admin;


 class Staff extends Authenticatable

{
    use HasFactory, Notifiable, HasRoles;

    protected $guard = 'staff';

    protected $fillable = [
        'staff_id',
        'name',
        'email',
        'password',
        'type',
        'department_id',
        'designation_id',
        'phone',
        'gender',
        'dob',
        'address',
        'qualification',
        'position',
        'employment_date',
        'marital_status',
        'next_of_kin',
        'next_of_kin_phone',
        'bank_name',
        'account_number',
        'tax_id',
    ];



 protected $casts = [
    'employment_date' => 'date',
    'dob' => 'date',
    'email_verified_at' => 'datetime',
    'password_changed_at' => 'datetime',
    'last_login_at' => 'datetime',
];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate staff ID
        static::creating(function ($staff) {
            $staff->staff_id = static::generateStaffId($staff->type);
        });

        // Create Admin record when Staff is created
        static::created(function ($staff) {
            Admin::updateOrCreate(
                ['email' => $staff->email], // Find by email (unique)
                [
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'password' => $staff->password,
                ]
            );
        });

        // Update Admin record when Staff is updated
        static::updated(function ($staff) {
            Admin::where('email', $staff->getOriginal('email'))->update([
                'name' => $staff->name,
                'email' => $staff->email,
            ]);
        });

        // Delete Admin record when Staff is deleted
        static::deleted(function ($staff) {
            Admin::where('email', $staff->email)->delete();
        });
    }

    /**
     * Generate a unique staff ID based on type
      */
public static function generateStaffId($type)
{
    $prefixes = [
        'clinical'      => 'CLI',
        'support'    => 'SUP',
        'administrative'     => 'ADM',

    ];

    $prefix = $prefixes[$type] ?? 'STAFF';

    $lastStaff = static::where('staff_id', 'like', "{$prefix}-%")
                       ->orderBy('staff_id', 'desc')
                       ->first();

    $number = $lastStaff
        ? (int) str_replace("{$prefix}-", "", $lastStaff->staff_id) + 1
        : 1;

    return sprintf("{$prefix}-%03d", $number);
}


    /**
     * Relationship to Department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }


    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }



    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }



    // In Staff model
public function scopePotentialHods($query)
{
    return $query->whereIn('position', ['administrative', 'clinical', 'support'])
                ->orWhere('type', 'support', 'clinical','');

}







}
