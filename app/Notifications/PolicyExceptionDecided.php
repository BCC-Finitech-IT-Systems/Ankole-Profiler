<?php

namespace App\Notifications;

use App\Models\PolicyPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyExceptionDecided extends Notification implements ShouldQueue
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
        $approved = $this->publication->exception_status === 'approved';

        return (new MailMessage)
            ->subject('Policy Exception ' . ($approved ? 'Approved' : 'Rejected') . ': ' . $policy->title)
            ->greeting('Hello,')
            ->line('Your exception request for "' . $policy->title . '" has been ' . ($approved ? 'approved' : 'rejected') . '.')
            ->action('View Policy', route('policies.adoption'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_exception_decided',
            'policy_publication_id' => $this->publication->id,
            'exception_status' => $this->publication->exception_status,
        ];
    }
}
