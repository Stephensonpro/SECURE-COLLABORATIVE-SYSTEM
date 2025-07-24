<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Add this line

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles; // Add this line

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];




     /**
     * Default guard name for permissions
     */
    protected $guard_name = 'admin'; // or 'admin' if you're using an admin guard



 
public function isAdmin()
{
    return $this->hasRole('admin');
}

public function isAirlineStaff()
{
    return $this->airlines()->exists();
}

public function isAirportStaff()
{
    return $this->airports()->exists();
}

public function isStaff()
{
    return $this->isAirlineStaff() || $this->isAirportStaff();
}

public function airlines()
{
    return $this->belongsToMany(Airline::class, 'airline_staff')
        ->withPivot('position', 'role');
}

public function airports()
{
    return $this->belongsToMany(Airport::class, 'airport_staff')
        ->withPivot('position', 'role');
}

public function complaints()
{
    return $this->hasMany(Complaint::class);
}



}
