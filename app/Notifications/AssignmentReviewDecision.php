<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentReviewDecision extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param string $decision One of 'accepted', 'returned', 'closed'. */
    public function __construct(public Assignment $assignment, public string $decision)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Assignment Review: ' . $this->assignment->title)
            ->greeting('Hello,')
            ->line("\"{$this->assignment->title}\" has been {$this->decision}.");

        if ($this->assignment->review_comment) {
            $mail->line('Comment: ' . $this->assignment->review_comment);
        }

        return $mail->action('View Assignment', route('assignments.show', $this->assignment));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_review_decision',
            'assignment_id' => $this->assignment->id,
            'decision' => $this->decision,
        ];
    }
}
