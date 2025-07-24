<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Announcement;
use App\Models\Staff;

class AnnouncementNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $announcement;
    public $staff;

    public function __construct(Announcement $announcement, Staff $staff)
    {
        $this->announcement = $announcement;
        $this->staff = $staff;
    }

    public function build()
    {
        return $this->subject('New Announcement: ' . $this->announcement->title)
                    ->view('emails.announcement');
    }
}
