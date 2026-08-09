<?php

namespace App\Notifications;

use App\Models\LandParcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandParcelDeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param string $reason One of 'completion', 'lease_expiry', 'stalled_query'. */
    public function __construct(public LandParcel $parcel, public string $reason)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Land Parcel Reminder: ' . $this->parcel->property_name)
            ->greeting('Hello,');

        match ($this->reason) {
            'completion' => $mail->line("\"{$this->parcel->property_name}\" ({$this->parcel->reference_number}) has passed its expected completion date."),
            'lease_expiry' => $mail->line("The lease on \"{$this->parcel->property_name}\" expires on {$this->parcel->lease_expiry_date->format('d M Y')}."),
            'stalled_query' => $mail->line("\"{$this->parcel->property_name}\" has unresolved registry queries pending follow-up."),
            default => null,
        };

        return $mail->action('View Parcel', route('land-parcels.show', $this->parcel));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'land_parcel_deadline_reminder',
            'land_parcel_id' => $this->parcel->id,
            'reason' => $this->reason,
        ];
    }
}
