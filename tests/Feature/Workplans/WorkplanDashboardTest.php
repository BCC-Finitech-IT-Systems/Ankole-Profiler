<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDashboard;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanDashboardTest extends TestCase
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
            'department_id' => $this->department->id, 'organization_id' => $this->organization->id,
        ]);
    }

    private function admin()
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-workplan-dashboard']);
    }

    public function test_bucket_counts_are_correct()
    {
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'completed']);
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'in_progress']);
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'not_started']);
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id, 'status' => 'deferred']);
        WorkplanActivity::factory()->create([
            'workplan_id' => $this->workplan->id, 'status' => 'in_progress', 'end_date' => now()->subDays(2)->toDateString(),
        ]);

        $component = Livewire::actingAs($this->admin())->test(WorkplanDashboard::class);

        $this->assertEquals(5, $component->viewData('total'));
        $this->assertEquals(1, $component->viewData('completed'));
        $this->assertEquals(2, $component->viewData('ongoing'));
        $this->assertEquals(1, $component->viewData('pending'));
        $this->assertEquals(1, $component->viewData('deferred'));
        $this->assertEquals(1, $component->viewData('overdueCount'));
    }

    public function test_department_filter_scopes_the_result_set()
    {
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id]);

        $otherDepartment = Department::factory()->create(['organization_id' => $this->organization->id]);
        $otherWorkplan = Workplan::factory()->approved()->create(['department_id' => $otherDepartment->id, 'organization_id' => $this->organization->id]);
        WorkplanActivity::factory()->create(['workplan_id' => $otherWorkplan->id]);

        $component = Livewire::actingAs($this->admin())
            ->test(WorkplanDashboard::class)
            ->set('departmentFilter', $this->department->id);

        $this->assertEquals(1, $component->viewData('total'));
    }

    public function test_counts_exclude_other_organizations()
    {
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplan->id]);

        $foreignOrg = Organization::factory()->create();
        $foreignDept = Department::factory()->create(['organization_id' => $foreignOrg->id]);
        $foreignWorkplan = Workplan::factory()->approved()->create(['department_id' => $foreignDept->id, 'organization_id' => $foreignOrg->id]);
        WorkplanActivity::factory()->create(['workplan_id' => $foreignWorkplan->id]);

        $component = Livewire::actingAs($this->admin())->test(WorkplanDashboard::class);

        $this->assertEquals(1, $component->viewData('total'));
    }
}
