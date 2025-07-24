<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Document extends Model
{
    use HasFactory, HasRoles;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_confidential'
    ];

    public function user()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Role::class, 'document_permissions')
            ->withPivot(['can_view', 'can_download', 'can_edit']);
    }

    public function accessLogs()
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    public function scopeAccessibleBy($query, $user)
    {
        return $query->where(function($q) use ($user) {
            // Documents owned by the user
            $q->where('user_id', $user->id)

              // Public documents (if you have this concept)
              ->orWhere('is_confidential', false)

              // Documents where user has permission through their role
              ->orWhereHas('permissions', function($q) use ($user) {
                  $q->whereIn('role_id', $user->roles->pluck('id'))
                    ->where('can_view', true);
              });
        });
    }
}
