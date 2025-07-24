<?php

 namespace App\Mail;

use App\Models\Complaint;
use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewComplaintNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $complaint;
    public $staff;

    public function __construct(Complaint $complaint, Staff $staff = null)
    {
        $this->complaint = $complaint;
        $this->staff = $staff;
    }

    public function build()
    {
        return $this->markdown('emails.staff.new_complaint_notification')
                   ->subject('New Complaint Received: ' . $this->complaint->ticket_number);
    }
}
