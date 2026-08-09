<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDetail;
use App\Livewire\Assignments\AssignmentsManagement;
use App\Models\AuditLog;
use App\Models\Assignment;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentAuditTrailTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    public function test_priority_change_writes_a_distinct_audit_row()
    {
        $assignment = Assignment::factory()->create(['organization_id' => $this->organization->id, 'priority' => 'medium']);
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-assignments', 'edit-assignments']);

        Livewire::actingAs($admin)
            ->test(AssignmentsManagement::class)
            ->call('create'); // resets form state, not used further

        // Directly exercise the tracked-field diff, mirroring how the
        // component calls it after ->update().
        $before = Assignment::trackedFieldSnapshot($assignment);
        $assignment->update(['priority' => 'urgent']);
        $assignment->logFieldChanges($before);

        $log = AuditLog::where('event', 'assignment.field_changed')
            ->where('auditable_id', $assignment->id)
            ->get()
            ->firstWhere(fn ($l) => $l->properties['field'] === 'priority');

        $this->assertNotNull($log);
        $this->assertEquals('medium', $log->properties['old']);
        $this->assertEquals('urgent', $log->properties['new']);
    }

    public function test_status_change_via_review_is_audited()
    {
        $assignment = Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'awaiting_review']);
        $reviewer = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-assignments', 'review-assignments']);

        Livewire::actingAs($reviewer)
            ->test(AssignmentDetail::class, ['assignment' => $assignment])
            ->call('accept');

        $this->assertEquals(1, AuditLog::where('event', 'assignment.accepted')->where('auditable_id', $assignment->id)->count());
        $this->assertGreaterThanOrEqual(1, AuditLog::where('event', 'assignment.field_changed')
            ->where('auditable_id', $assignment->id)
            ->get()
            ->filter(fn ($l) => $l->properties['field'] === 'status')
            ->count());
    }
}
