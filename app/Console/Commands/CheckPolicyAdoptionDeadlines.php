<?php

namespace App\Console\Commands;

use App\Models\PolicyPublication;
use App\Notifications\PolicyAdoptionDeadlineApproaching;
use App\Notifications\PolicyAdoptionOverdue;
use App\Services\AuditLogger;
use App\Services\PolicyNotificationTargets;
use Illuminate\Console\Command;

class CheckPolicyAdoptionDeadlines extends Command
{
    protected $signature = 'policies:check-adoption-deadlines';

    protected $description = 'Flip overdue policy adoptions and send deadline-approaching / overdue reminders.';

    private const APPROACHING_WINDOWS = [14, 7, 1];

    public function handle(): int
    {
        $this->flagOverdue();
        $this->sendApproachingReminders();
        $this->sendOverdueReminders();

        return self::SUCCESS;
    }

    private function eligibleQuery()
    {
        return PolicyPublication::whereNotNull('due_date')
            ->whereNotIn('status', ['adopted', 'partially_adopted'])
            ->where('exception_status', '!=', 'approved');
    }

    private function flagOverdue(): void
    {
        $this->eligibleQuery()
            ->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'overdue')
            ->each(function (PolicyPublication $publication) {
                $publication->update(['status' => 'overdue']);
                AuditLogger::record(
                    $publication,
                    'adoption.marked_overdue',
                    [],
                    $publication->policy->organization_id,
                    $publication->organization_id,
                    actorUserId: null,
                );
            });
    }

    private function sendApproachingReminders(): void
    {
        foreach (self::APPROACHING_WINDOWS as $daysOut) {
            $targetDate = now()->addDays($daysOut)->toDateString();

            $this->eligibleQuery()
                ->whereDate('due_date', $targetDate)
                ->where(function ($q) {
                    $q->whereNull('last_reminder_sent_at')
                      ->orWhereDate('last_reminder_sent_at', '!=', now()->toDateString());
                })
                ->with('organization', 'policy')
                ->get()
                ->each(function (PolicyPublication $publication) {
                    $this->notifyInstitution($publication, new PolicyAdoptionDeadlineApproaching($publication));
                    $publication->update(['last_reminder_sent_at' => now()]);
                });
        }
    }

    private function sendOverdueReminders(): void
    {
        $this->eligibleQuery()
            ->where('status', 'overdue')
            ->where(function ($q) {
                $q->whereNull('last_reminder_sent_at')
                  ->orWhereDate('last_reminder_sent_at', '!=', now()->toDateString());
            })
            ->with('organization', 'policy')
            ->get()
            ->each(function (PolicyPublication $publication) {
                $this->notifyInstitution($publication, new PolicyAdoptionOverdue($publication));
                $publication->update(['last_reminder_sent_at' => now()]);
            });
    }

    private function notifyInstitution(PolicyPublication $publication, $notification): void
    {
        foreach (PolicyNotificationTargets::forInstitution($publication->organization) as $recipient) {
            $recipient->notify($notification);
        }
    }
}
