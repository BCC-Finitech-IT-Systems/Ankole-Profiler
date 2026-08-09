<?php

namespace App\Notifications;

use App\Models\PolicyVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyIssued extends Notification implements ShouldQueue
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
            ->subject('New Policy Issued: ' . $policy->title)
            ->greeting('Hello,')
            ->line("A new policy has been issued by {$policy->organization->display_name}.")
            ->line("Policy: {$policy->title} (v{$this->version->version_label})")
            ->when($this->version->adoption_due_date, fn ($mail) => $mail->line('Adoption due by: ' . $this->version->adoption_due_date->format('d M Y')))
            ->action('View Policy', route('policies.adoption'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'policy_issued',
            'policy_id' => $this->version->policy_id,
            'policy_version_id' => $this->version->id,
            'title' => $this->version->policy->title,
        ];
    }
}
