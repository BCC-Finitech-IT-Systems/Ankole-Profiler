<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use App\Models\WorkplanProgressUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanProgressTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;
    private Workplan $workplan;
    private WorkplanActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
        $this->workplan = Workplan::factory()->approved()->create([
            'department_id' => $this->department->id, 'organization_id' => $this->organization->id,
        ]);
        $this->activity = WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id]);
    }

    private function manager()
    {
        return $this->affiliatedUser('Department Manager', $this->organization, $this->department, [
            'view-workplans', 'record-workplan-progress',
        ]);
    }

    public function test_recording_progress_creates_a_progress_update_and_updates_the_activity()
    {
        Livewire::actingAs($this->manager())
            ->test(WorkplanDetail::class, ['workplan' => $this->workplan])
            ->call('openProgressForm', $this->activity->id)
            ->set('progress_percent_complete', 60)
            ->set('progressStatus', 'in_progress')
            ->set('work_completed', 'Drafted the training material')
            ->call('recordProgress')
            ->assertHasNoErrors();

        $this->assertEquals(1, WorkplanProgressUpdate::where('workplan_activity_id', $this->activity->id)->count());
        $this->activity->refresh();
        $this->assertEquals(60, $this->activity->percent_complete);
        $this->assertEquals('in_progress', $this->activity->status);
    }

    public function test_recording_progress_is_blocked_while_the_workplan_is_still_draft()
    {
        $draftWorkplan = Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id, 'year' => 2030]);
        $draftActivity = WorkplanActivity::factory()->create(['workplan_id' => $draftWorkplan->id]);

        Livewire::actingAs($this->manager())
            ->test(WorkplanDetail::class, ['workplan' => $draftWorkplan])
            ->call('openProgressForm', $draftActivity->id)
            ->assertForbidden();
    }

    public function test_overdue_activity_is_flagged()
    {
        $overdue = WorkplanActivity::factory()->create([
            'workplan_id' => $this->workplan->id,
            'end_date' => now()->subDays(3)->toDateString(),
            'status' => 'in_progress',
        ]);
        $notOverdue = WorkplanActivity::factory()->create([
            'workplan_id' => $this->workplan->id,
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'in_progress',
        ]);
        $completedPastDue = WorkplanActivity::factory()->create([
            'workplan_id' => $this->workplan->id,
            'end_date' => now()->subDays(3)->toDateString(),
            'status' => 'completed',
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($notOverdue->isOverdue());
        $this->assertFalse($completedPastDue->isOverdue());
    }
}
