<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Assignment Updated: ' . $this->assignment->title)
            ->greeting('Hello,')
            ->line("\"{$this->assignment->title}\" is now " . str_replace('_', ' ', $this->assignment->status) . '.')
            ->action('View Assignment', route('assignments.show', $this->assignment));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_status_changed',
            'assignment_id' => $this->assignment->id,
            'status' => $this->assignment->status,
        ];
    }
}
