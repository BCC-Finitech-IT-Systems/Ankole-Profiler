<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Notifications\AssignmentDeadlineReminder;
use App\Services\AssignmentNotificationTargets;
use Illuminate\Console\Command;

class CheckAssignmentDeadlines extends Command
{
    protected $signature = 'assignments:check-deadlines';

    protected $description = 'Send deadline-approaching and overdue reminders for open assignments.';

    private const APPROACHING_WINDOWS = [14, 7, 1];

    private const OPEN_STATUSES = ['not_started', 'in_progress', 'blocked', 'awaiting_review'];

    public function handle(): int
    {
        $this->sendApproachingReminders();
        $this->sendOverdueReminders();

        return self::SUCCESS;
    }

    private function eligibleQuery()
    {
        return Assignment::whereNotNull('due_date')->whereIn('status', self::OPEN_STATUSES);
    }

    private function notSentToday($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_reminder_sent_at')
              ->orWhereDate('last_reminder_sent_at', '!=', now()->toDateString());
        });
    }

    private function sendApproachingReminders(): void
    {
        foreach (self::APPROACHING_WINDOWS as $daysOut) {
            $targetDate = now()->addDays($daysOut)->toDateString();

            $this->notSentToday($this->eligibleQuery()->whereDate('due_date', $targetDate))
                ->get()
                ->each(function (Assignment $assignment) {
                    $this->notify($assignment, false);
                });
        }
    }

    private function sendOverdueReminders(): void
    {
        // Effective due date is revised_due_date when set, else due_date —
        // matches Assignment::isOverdue()'s own logic.
        $this->notSentToday(
            $this->eligibleQuery()->whereRaw('COALESCE(revised_due_date, due_date) < ?', [now()->toDateString()])
        )
            ->get()
            ->each(function (Assignment $assignment) {
                $this->notify($assignment, true);
            });
    }

    private function notify(Assignment $assignment, bool $overdue): void
    {
        foreach (AssignmentNotificationTargets::forAssignment($assignment) as $recipient) {
            $recipient->notify(new AssignmentDeadlineReminder($assignment, $overdue));
        }

        $assignment->update(['last_reminder_sent_at' => now()]);
    }
}
