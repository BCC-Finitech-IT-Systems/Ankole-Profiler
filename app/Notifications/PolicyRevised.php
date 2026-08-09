<?php

namespace App\Notifications;

use App\Models\PolicyVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyRevised extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PolicyVersion $version)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $policy = $this->version->policy;

        return (new MailMessage)
            ->subject('Policy Revised: ' . $policy->title)
            ->greeting('Hello,')
            ->line("{$policy->title} has been revised to version {$this->version->version_label}.")
            ->when($this->version->adoption_due_date, fn ($mail) => $mail->line('New adoption due date: ' . $this->version->adoption_due_date->format('d M Y')))
            ->action('View Policy', route('policies.adoption'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_revised',
            'policy_id' => $this->version->policy_id,
            'policy_version_id' => $this->version->id,
            'title' => $this->version->policy->title,
        ];
    }
}
