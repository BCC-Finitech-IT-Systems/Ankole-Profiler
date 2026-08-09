<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDetail;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanAuditTrailTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;
    private Workplan $workplan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
        $this->workplan = Workplan::factory()->create([
            'department_id' => $this->department->id, 'organization_id' => $this->organization->id,
        ]);
    }

    public function test_approve_writes_an_audit_row()
    {
        $this->workplan->update(['status' => 'submitted', 'submitted_at' => now()]);
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'approve-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('approve');

        $this->assertEquals(1, AuditLog::where('event', 'workplan.approved')
            ->where('auditable_type', Workplan::class)
            ->where('auditable_id', $this->workplan->id)
            ->count());
    }

    public function test_progress_update_writes_an_audit_row()
    {
        $this->workplan->update(['status' => 'approved']);
        $activity = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id]);
        $manager = $this->affiliatedUser('Department Manager', $this->organization, $this->department, ['view-workplans', 'record-workplan-progress']);

        Livewire::actingAs($manager)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('openProgressForm', $activity->id)
            ->set('progress_percent_complete', 25)
            ->call('recordProgress');

        $this->assertEquals(1, AuditLog::where('event', 'workplan_activity.progress_recorded')
            ->where('auditable_id', $activity->id)
            ->count());
    }

    public function test_carry_forward_writes_an_audit_row()
    {
        $this->workplan->update(['status' => 'approved']);
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'carry-forward-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('carryForward');

        $this->assertEquals(1, AuditLog::where('event', 'workplan.carried_forward')->count());
    }
}
