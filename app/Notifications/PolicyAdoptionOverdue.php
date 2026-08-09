<?php

namespace App\Notifications;

use App\Models\PolicyPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyAdoptionOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PolicyPublication $publication)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $policy = $this->publication->policy;

        return (new MailMessage)
            ->subject('Overdue Policy Adoption: ' . $policy->title)
            ->greeting('Hello,')
            ->line("Adoption of \"{$policy->title}\" was due on {$this->publication->due_date->format('d M Y')} and is now overdue.")
            ->action('Report Adoption', route('policies.adoption'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_adoption_overdue',
            'policy_publication_id' => $this->publication->id,
            'due_date' => $this->publication->due_date?->toDateString(),
        ];
    }
}
