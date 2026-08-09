<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentDeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Assignment $assignment, public bool $overdue)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $dueDate = $this->assignment->revised_due_date ?? $this->assignment->due_date;

        $mail = (new MailMessage)->subject(($this->overdue ? 'Overdue' : 'Deadline Approaching') . ': ' . $this->assignment->title)
            ->greeting('Hello,');

        if ($this->overdue) {
            $mail->line("\"{$this->assignment->title}\" was due on {$dueDate->format('d M Y')} and is now overdue.");
        } else {
            $mail->line("\"{$this->assignment->title}\" is due on {$dueDate->format('d M Y')}.");
        }

        return $mail->action('View Assignment', route('assignments.show', $this->assignment));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_deadline_reminder',
            'assignment_id' => $this->assignment->id,
            'overdue' => $this->overdue,
        ];
    }
}
