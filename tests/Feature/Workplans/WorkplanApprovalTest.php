<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanApprovalTest extends TestCase
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

    public function test_department_manager_can_submit_a_draft_workplan()
    {
        $manager = $this->affiliatedUser('Department Manager', $this->organization, $this->department, ['view-workplans', 'submit-workplans']);

        Livewire::actingAs($manager)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->set('review_comment', 'Ready for review')
            ->call('submit')
            ->assertHasNoErrors();

        $this->workplan->refresh();
        $this->assertEquals('submitted', $this->workplan->status);
        $this->assertNotNull($this->workplan->submitted_at);
    }

    public function test_department_manager_cannot_approve_their_own_departments_workplan()
    {
        $this->workplan->update(['status' => 'submitted', 'submitted_at' => now()]);

        $manager = $this->affiliatedUser('Department Manager', $this->organization, $this->department, [
            'view-workplans', 'approve-workplans',
        ]);

        Livewire::actingAs($manager)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('approve')
            ->assertForbidden();
    }

    public function test_org_admin_can_approve_with_a_comment()
    {
        $this->workplan->update(['status' => 'submitted', 'submitted_at' => now()]);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'approve-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->set('decision_comment', 'Looks good, approved.')
            ->call('approve')
            ->assertHasNoErrors();

        $this->workplan->refresh();
        $this->assertEquals('approved', $this->workplan->status);
        $this->assertEquals('Looks good, approved.', $this->workplan->decision_comment);
        $this->assertNotNull($this->workplan->approved_at);
    }

    public function test_reject_requires_a_reason_and_returns_the_plan_to_draft()
    {
        $this->workplan->update(['status' => 'submitted', 'submitted_at' => now()]);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'approve-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('reject')
            ->assertHasErrors(['decision_comment']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->set('decision_comment', 'Missing budget detail.')
            ->call('reject')
            ->assertHasNoErrors();

        $this->workplan->refresh();
        $this->assertEquals('draft', $this->workplan->status);
        $this->assertEquals('Missing budget detail.', $this->workplan->decision_comment);
    }
}
