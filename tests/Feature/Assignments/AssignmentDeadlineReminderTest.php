<?php

namespace Tests\Feature\Assignments;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Notifications\AssignmentDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AssignmentDeadlineReminderTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    private function assignmentWithLeadUser(string $dueDate, string $status = 'in_progress'): Assignment
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        return Assignment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => $status,
            'due_date' => $dueDate,
            'responsible_person_id' => $person->id,
        ]);
    }

    public function test_overdue_assignment_receives_a_reminder()
    {
        Notification::fake();

        $assignment = $this->assignmentWithLeadUser(now()->subDays(2)->toDateString());

        $this->artisan('assignments:check-deadlines');

        Notification::assertSentTo($assignment->responsiblePerson->user, AssignmentDeadlineReminder::class);
        $this->assertNotNull($assignment->fresh()->last_reminder_sent_at);
    }

    public function test_reminder_is_not_resent_the_same_day()
    {
        Notification::fake();

        $assignment = $this->assignmentWithLeadUser(now()->subDays(2)->toDateString());

        $this->artisan('assignments:check-deadlines');
        Notification::assertSentToTimes($assignment->responsiblePerson->user, AssignmentDeadlineReminder::class, 1);

        Notification::fake();
        $this->artisan('assignments:check-deadlines');
        Notification::assertNothingSent();
    }

    public function test_completed_and_cancelled_assignments_are_skipped()
    {
        Notification::fake();

        $this->assignmentWithLeadUser(now()->subDays(2)->toDateString(), 'completed');
        $this->assignmentWithLeadUser(now()->subDays(2)->toDateString(), 'cancelled');

        $this->artisan('assignments:check-deadlines');

        Notification::assertNothingSent();
    }

    public function test_approaching_deadline_at_seven_days_sends_a_reminder()
    {
        Notification::fake();

        $assignment = $this->assignmentWithLeadUser(now()->addDays(7)->toDateString());

        $this->artisan('assignments:check-deadlines');

        Notification::assertSentTo($assignment->responsiblePerson->user, AssignmentDeadlineReminder::class);
    }
}
