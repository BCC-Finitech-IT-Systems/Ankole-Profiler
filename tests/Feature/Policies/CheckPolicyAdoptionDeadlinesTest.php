<?php

namespace Tests\Feature\Policies;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use App\Notifications\PolicyAdoptionDeadlineApproaching;
use App\Notifications\PolicyAdoptionOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class CheckPolicyAdoptionDeadlinesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private PolicyVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
        $this->version = PolicyVersion::factory()->published()->create(['policy_id' => $policy->id, 'version_number' => 1]);
    }

    private function makePublication(string $status, string $dueDate, string $exceptionStatus = 'none'): PolicyPublication
    {
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        return PolicyPublication::create([
            'policy_version_id' => $this->version->id,
            'policy_id' => $this->version->policy_id,
            'organization_id' => $institution->id,
            'status' => $status,
            'due_date' => $dueDate,
        ]);
    }

    public function test_past_due_publications_are_flagged_overdue()
    {
        $publication = $this->makePublication('sent', now()->subDays(2)->toDateString());

        $this->artisan('policies:check-adoption-deadlines');

        $this->assertEquals('overdue', $publication->fresh()->status);
    }

    public function test_adopted_and_approved_exception_publications_are_not_flagged()
    {
        $adopted = $this->makePublication('adopted', now()->subDays(2)->toDateString());
        $excepted = $this->makePublication('sent', now()->subDays(2)->toDateString(), 'approved');
        $excepted->update(['exception_status' => 'approved']);

        $this->artisan('policies:check-adoption-deadlines');

        $this->assertEquals('adopted', $adopted->fresh()->status);
        $this->assertEquals('sent', $excepted->fresh()->status);
    }

    public function test_overdue_flip_writes_an_audit_row_with_a_null_actor()
    {
        $publication = $this->makePublication('sent', now()->subDays(2)->toDateString());

        $this->artisan('policies:check-adoption-deadlines');

        $log = AuditLog::where('event', 'adoption.marked_overdue')
            ->where('auditable_id', $publication->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->actor_user_id);
    }

    public function test_approaching_deadline_sends_a_reminder_and_is_not_resent_the_same_day()
    {
        Notification::fake();

        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $this->affiliatedUser('Organization Admin', $institution, null, ['view-policy-adoption']);

        $publication = PolicyPublication::create([
            'policy_version_id' => $this->version->id,
            'policy_id' => $this->version->policy_id,
            'organization_id' => $institution->id,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->artisan('policies:check-adoption-deadlines');
        Notification::assertCount(1);

        Notification::fake();
        $this->artisan('policies:check-adoption-deadlines');
        Notification::assertNothingSent();

        $this->assertNotNull($publication->fresh()->last_reminder_sent_at);
    }
}
