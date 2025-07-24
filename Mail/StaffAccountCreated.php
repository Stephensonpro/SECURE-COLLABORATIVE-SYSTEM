<?php

namespace App\Mail;

use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $staff;
    public $password;

    public function __construct(Staff $staff, $password)
    {
        $this->staff = $staff;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Your LCU HRM Account Credentials')
                    ->markdown('emails.staff.account-created');
    }
}
