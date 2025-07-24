<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;


class Admin extends Authenticatable
{
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];










    // Messages sent by this admin
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // Messages received by this admin
    public function receivedMessages()
    {
        return $this->belongsToMany(Message::class, 'message_recipient', 'recipient_id', 'message_id')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }






    /**
     * Default guard name for permissions
     */
    protected $guard_name = 'admin';

    /**
     * Get the guard name for the model.
     *
     * @return string
     */
    public function guardName()
    {
        return 'admin';
    }



    // In your Admin model
public function scopeActive(Builder $query): Builder
{
    return $query->where('status', 'active'); // Assuming you have a status column
}



public function canViewAttachment(MessageAttachment $attachment)
{
    // Admins can view all attachments (if you want this)
    if ($this->hasRole('admin')) {
        return true;
    }

    // Sender can view
    if ($attachment->message->sender_id === $this->id) {
        return true;
    }

    // Recipient can view
    return $attachment->message->recipients()
        ->where('recipient_id', $this->id)
        ->exists();
}





















// Add these methods to your Admin model
public function canViewDocument(Document $document)
{
    // Admin can view all documents
    if ($this->hasRole('admin')) {
        return true;
    }

    // Document owner can always view
    if ($document->user_id === $this->id) {
        return true;
    }

    // Check role-based permissions
    return $document->permissions()
        ->whereIn('role_id', $this->roles->pluck('id'))
        ->where('can_view', true)
        ->exists();
}

public function canDownloadDocument(Document $document)
{
    // Admin can download all documents
    if ($this->hasRole('admin')) {
        return true;
    }

    // Document owner can always download
    if ($document->user_id === $this->id) {
        return true;
    }

    // Check role-based permissions
    return $document->permissions()
        ->whereIn('role_id', $this->roles->pluck('id'))
        ->where('can_download', true)
        ->exists();
}

public function canEditDocument(Document $document)
{
    // Admin can edit all documents
    if ($this->hasRole('admin')) {
        return true;
    }

    // Document owner can always edit
    if ($document->user_id === $this->id) {
        return true;
    }

    // Check role-based permissions
    return $document->permissions()
        ->whereIn('role_id', $this->roles->pluck('id'))
        ->where('can_edit', true)
        ->exists();
}

public function canViewDocumentLogs(Document $document)
{
    // Only admin and document owner can view logs
    return $this->hasRole('admin') || $document->user_id === $this->id;
}
}
