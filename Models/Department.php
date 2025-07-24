<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'hod_id',
        'status'
    ];

    // Relationship to HOD (Head of Department)
    public function hod(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_id')->withDefault([
            'name' => 'Not Assigned',
            'profile_picture' => null
        ]);
    }

    // Relationship to all staff in this department
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'department_id');
    }

    // Relationship to tasks
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Relationship to announcements
    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_department');
    }

    // Scope for active departments
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // Scope for inactive departments
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    // Add any other status scopes you might need
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }



    public function permissions()
{
    return $this->belongsToMany(Role::class, 'document_permissions')
        ->withPivot(['can_view', 'can_download', 'can_edit']);
}

public function user()
{
    return $this->belongsTo(User::class);
}


}
