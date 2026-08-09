<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentAssigned extends Notification implements ShouldQueue
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
            ->subject('Assignment: ' . $this->assignment->title)
            ->greeting('Hello,')
            ->line("You have been assigned to: {$this->assignment->title}")
            ->when($this->assignment->due_date, fn ($mail) => $mail->line('Due: ' . $this->assignment->due_date->format('d M Y')))
            ->action('View Assignment', route('assignments.show', $this->assignment));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_assigned',
            'assignment_id' => $this->assignment->id,
            'title' => $this->assignment->title,
        ];
    }
}
