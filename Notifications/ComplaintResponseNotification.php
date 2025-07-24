<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $complaint,
        public $response = null
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusColor = $this->getStatusColor($this->complaint->status);

        return (new MailMessage)
            ->subject('Update on Your Complaint #' . $this->complaint->ticket_number)
            ->view('emails.complaint_response', [
                'complaint' => $this->complaint,
                'response' => $this->response,
                'statusColor' => $statusColor,
                'passengerName' => $this->complaint->passenger->first_name
            ]);
    }

    protected function getStatusColor($status)
    {
        return match($status) {
            'resolved' => '#4CAF50', // Green
            'in_progress' => '#2196F3', // Blue
            'rejected' => '#F44336', // Red
            default => '#002366', // Default navy blue
        };
    }
}
