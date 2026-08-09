<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDashboard;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function admin()
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-assignment-dashboard']);
    }

    public function test_bucket_counts_are_correct()
    {
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'not_started']);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'in_progress']);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'blocked']);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'awaiting_review']);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'status' => 'completed']);

        $component = Livewire::actingAs($this->admin())->test(AssignmentDashboard::class);

        $this->assertEquals(5, $component->viewData('total'));
        $this->assertEquals(1, $component->viewData('notStarted'));
        $this->assertEquals(1, $component->viewData('inProgress'));
        $this->assertEquals(1, $component->viewData('blocked'));
        $this->assertEquals(1, $component->viewData('awaitingReview'));
        $this->assertEquals(1, $component->viewData('completed'));
    }

    public function test_department_filter_scopes_results()
    {
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);

        $otherDept = Department::factory()->create(['organization_id' => $this->organization->id]);
        Assignment::factory()->create(['organization_id' => $this->organization->id, 'department_id' => $otherDept->id]);

        $component = Livewire::actingAs($this->admin())
            ->test(AssignmentDashboard::class)
            ->set('departmentFilter', $this->department->id);

        $this->assertEquals(1, $component->viewData('total'));
    }

    public function test_counts_exclude_other_organizations()
    {
        Assignment::factory()->create(['organization_id' => $this->organization->id]);

        $foreignOrg = Organization::factory()->create();
        Assignment::factory()->create(['organization_id' => $foreignOrg->id]);

        $component = Livewire::actingAs($this->admin())->test(AssignmentDashboard::class);

        $this->assertEquals(1, $component->viewData('total'));
    }
}
