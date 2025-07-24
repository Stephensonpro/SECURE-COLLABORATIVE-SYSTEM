<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\AnnouncementNotification;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_pinned',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }




public function author()
{
    return $this->belongsTo(Admin::class, 'user_id'); // or User::class depending on your setup
}



    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_department');
    }

    /**
     * Send email notifications to department staff
     */
    public function notifyDepartments()
    {
        foreach ($this->departments as $department) {
            foreach ($department->staff as $staff) {
                if ($staff->email) {
                    Mail::to($staff->email)->send(
                        new AnnouncementNotification($this, $staff)
                    );
                }
            }
        }
    }
}
