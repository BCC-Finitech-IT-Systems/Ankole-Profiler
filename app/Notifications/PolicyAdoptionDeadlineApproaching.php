<?php

namespace App\Notifications;

use App\Models\PolicyPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyAdoptionDeadlineApproaching extends Notification implements ShouldQueue
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
            ->subject('Policy Adoption Deadline Approaching: ' . $policy->title)
            ->greeting('Hello,')
            ->line("Adoption of \"{$policy->title}\" is due by {$this->publication->due_date->format('d M Y')}.")
            ->action('Report Adoption', route('policies.adoption'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_adoption_deadline_approaching',
            'policy_publication_id' => $this->publication->id,
            'due_date' => $this->publication->due_date?->toDateString(),
        ];
    }
}
