<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'content'
    ];


protected $casts = [
    'read_at' => 'dateime',
];


    public function sender()
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(Admin::class, 'message_recipient', 'message_id', 'recipient_id')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }



    public function recipients()
{
    return $this->belongsToMany(User::class)
        ->withPivot('read_at')
        ->using(\App\Models\MessageRecipient::class);
}

}
