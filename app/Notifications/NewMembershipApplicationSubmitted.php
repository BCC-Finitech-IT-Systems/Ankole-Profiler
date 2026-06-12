<?php

namespace App\Notifications;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMembershipApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $person;

    public function __construct(Person $person)
    {
        $this->person = $person;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Membership Application Submitted')
            ->greeting('Hello Admin,')
            ->line('A new membership application has been submitted.')
            ->line('Applicant: ' . ($this->person->full_name ?? trim($this->person->given_name . ' ' . $this->person->family_name)))
            ->action('Review Applications', url('/organizations/membership-applications'))
            ->line('Thank you for using our system!');
    }
}
