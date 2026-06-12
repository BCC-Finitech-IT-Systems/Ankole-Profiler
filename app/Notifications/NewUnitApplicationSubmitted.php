<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\OrganizationUnitApplication;

class NewUnitApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $application;

    public function __construct(OrganizationUnitApplication $application)
    {
        $this->application = $application;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $person = $this->application->person;
        $unit = $this->application->unit;
        $lines = [
            'A new unit membership application has been submitted.',
            'Applicant: ' . ($person->full_name ?? ''),
            'Email: ' . ($person->primaryEmail()->email ?? 'N/A'),
            'Phone: ' . ($person->primaryPhone()->phone ?? 'N/A'),
            'Unit: ' . ($unit->name ?? ''),
            'Organization: ' . (optional($unit->Organization)->display_name ?? 'N/A'),
        ];
        return (new MailMessage)
            ->subject('New Unit Membership Application Submitted')
            ->greeting('Hello Admin,')
            ->line(implode("\n", $lines))
            ->action('Review Applications', url('/admin/unit-applications'))
            ->line('Thank you for using our system!');
    }
}
