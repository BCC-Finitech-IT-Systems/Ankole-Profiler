<?php

namespace App\Console\Commands;

use App\Models\LandParcel;
use App\Notifications\LandParcelDeadlineReminder;
use App\Services\LandParcelNotificationTargets;
use Illuminate\Console\Command;

class CheckLandParcelDeadlines extends Command
{
    protected $signature = 'land:check-deadlines';

    protected $description = 'Send reminders for stalled land applications, expiring leases, and unresolved registry queries.';

    private const CLOSED_STAGES = ['title_issued', 'closed'];
    private const LEASE_EXPIRY_WINDOW_DAYS = 90;

    public function handle(): int
    {
        $this->remindStalledApplications();
        $this->remindExpiringLeases();
        $this->remindStalledQueries();

        return self::SUCCESS;
    }

    private function notSentToday($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_reminder_sent_at')
              ->orWhereDate('last_reminder_sent_at', '!=', now()->toDateString());
        });
    }

    private function remindStalledApplications(): void
    {
        $this->notSentToday(
            LandParcel::whereNotNull('expected_completion_date')
                ->where('expected_completion_date', '<', now()->toDateString())
                ->whereNotIn('stage', self::CLOSED_STAGES)
        )->get()->each(fn (LandParcel $parcel) => $this->notify($parcel, 'completion'));
    }

    private function remindExpiringLeases(): void
    {
        $this->notSentToday(
            LandParcel::whereNotNull('lease_expiry_date')
                ->where('lease_expiry_date', '>=', now()->toDateString())
                ->where('lease_expiry_date', '<=', now()->addDays(self::LEASE_EXPIRY_WINDOW_DAYS)->toDateString())
        )->get()->each(fn (LandParcel $parcel) => $this->notify($parcel, 'lease_expiry'));
    }

    private function remindStalledQueries(): void
    {
        // No extra "how long stalled" threshold: last_reminder_sent_at is
        // itself touched by notify()'s update(), which would also bump
        // updated_at and silently defeat an updated_at-based staleness
        // check on the next run. Daily dedup alone satisfies "identify
        // stalled cases without duplicate notifications".
        $this->notSentToday(
            LandParcel::where('stage', 'queries_raised')
        )->get()->each(fn (LandParcel $parcel) => $this->notify($parcel, 'stalled_query'));
    }

    private function notify(LandParcel $parcel, string $reason): void
    {
        foreach (LandParcelNotificationTargets::forParcel($parcel) as $recipient) {
            $recipient->notify(new LandParcelDeadlineReminder($parcel, $reason));
        }

        $parcel->update(['last_reminder_sent_at' => now()]);
    }
}
