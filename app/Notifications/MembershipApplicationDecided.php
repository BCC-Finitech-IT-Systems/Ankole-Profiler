<?php

namespace App\Notifications;

use App\Models\PersonAffiliation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public $affiliation;

    public function __construct(PersonAffiliation $affiliation)
    {
        $this->affiliation = $affiliation;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $organization = $this->affiliation->Organization?->display_name ?? 'the organization';
        $approved = $this->affiliation->status === 'active';

        $mail = (new MailMessage)
            ->subject('Membership Application ' . ($approved ? 'Approved' : 'Declined'))
            ->greeting('Hello,');

        if ($approved) {
            $mail->line("Your membership application to {$organization} has been approved.")
                ->line('You can now sign in and access member features.')
                ->action('Sign In', url('/login'));
        } else {
            $mail->line("Your membership application to {$organization} was declined.")
                ->line('Please contact the organization for more information.');
        }

        return $mail->line('Thank you for using our system!');
    }
}
