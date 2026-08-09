<?php

namespace App\Notifications;

use App\Models\PolicyPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyExceptionRequested extends Notification implements ShouldQueue
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
        $institution = $this->publication->organization->display_name ?? $this->publication->organization->legal_name;

        return (new MailMessage)
            ->subject('Policy Exception Requested: ' . $policy->title)
            ->greeting('Hello,')
            ->line("{$institution} has requested an exception for \"{$policy->title}\".")
            ->line('Reason: ' . ($this->publication->exception_reason ?? '—'))
            ->action('Review Request', route('policies.dashboard'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_exception_requested',
            'policy_publication_id' => $this->publication->id,
        ];
    }
}
