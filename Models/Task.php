<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'assigned_to',
        'department_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at'
    ];
 

protected $casts = [
    'due_date' => 'datetime',
    'completed_at' => 'datetime',
];



    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }
}

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'comment'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }
}

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
