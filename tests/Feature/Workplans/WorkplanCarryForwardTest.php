<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanCarryForwardTest extends TestCase
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
        $this->workplan = Workplan::factory()->approved()->create([
            'department_id' => $this->department->id, 'organization_id' => $this->organization->id, 'year' => 2026,
        ]);
    }

    public function test_carry_forward_copies_only_unfinished_activities_into_the_next_year()
    {
        $notStarted = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'not_started', 'activity' => 'Not started task']);
        $inProgress = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'in_progress', 'activity' => 'In progress task']);
        $deferred = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'deferred', 'activity' => 'Deferred task']);
        $completed = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'completed', 'activity' => 'Completed task']);
        $cancelled = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'cancelled', 'activity' => 'Cancelled task']);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'carry-forward-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('carryForward');

        $newWorkplan = Workplan::where('department_id', $this->department->id)->where('year', 2027)->first();
        $this->assertNotNull($newWorkplan);
        $this->assertEquals('draft', $newWorkplan->status);
        $this->assertEquals($this->workplan->id, $newWorkplan->carried_forward_from_id);

        $carriedActivities = $newWorkplan->activities;
        $this->assertEquals(3, $carriedActivities->count());
        $this->assertEqualsCanonicalizing(
            ['Not started task', 'In progress task', 'Deferred task'],
            $carriedActivities->pluck('activity')->all()
        );

        foreach ($carriedActivities as $activity) {
            $this->assertEquals('not_started', $activity->status);
            $this->assertNotNull($activity->carried_forward_from_activity_id);
        }
    }

    public function test_source_workplan_and_activities_are_unchanged_after_carry_forward()
    {
        $activity = WorkplanActivity::factory()->create([
            'workplan_id' => $this->workplan->id, 'status' => 'in_progress', 'percent_complete' => 40,
        ]);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplans', 'carry-forward-workplans']);

        Livewire::actingAs($admin)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('carryForward');

        $this->assertEquals('approved', $this->workplan->fresh()->status);
        $this->assertEquals('in_progress', $activity->fresh()->status);
        $this->assertEquals(40, $activity->fresh()->percent_complete);
    }
}
