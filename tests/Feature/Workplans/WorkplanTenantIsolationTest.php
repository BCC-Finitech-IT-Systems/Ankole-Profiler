<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplanDashboard;
use App\Livewire\Workplans\WorkplanDetail;
use App\Livewire\Workplans\WorkplansManagement;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $orgA;
    private Organization $orgB;
    private Department $deptA;
    private Workplan $workplanA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();
        $this->deptA = Department::factory()->create(['organization_id' => $this->orgA->id]);
        $this->workplanA = Workplan::factory()->create([
            'department_id' => $this->deptA->id, 'organization_id' => $this->orgA->id, 'title' => 'Org A Workplan',
        ]);
    }

    public function test_list_excludes_other_organizations()
    {
        $deptB = Department::factory()->create(['organization_id' => $this->orgB->id]);
        Workplan::factory()->create(['department_id' => $deptB->id, 'organization_id' => $this->orgB->id, 'title' => 'Org B Workplan']);

        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-workplans']);

        Livewire::actingAs($adminB)
            ->test(WorkplansManagement::class)
            ->assertDontSee('Org A Workplan')
            ->assertSee('Org B Workplan');
    }

    public function test_direct_show_of_a_foreign_workplan_is_forbidden()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-workplans']);

        Livewire::actingAs($adminB)
            ->test(WorkplanDetail::class, ['workplan' => $this->workplanA])
            ->assertForbidden();
    }

    public function test_dashboard_excludes_other_organizations()
    {
        $this->workplanA->update(['status' => 'approved']);
        WorkplanActivity::factory()->create(['workplan_id' => $this->workplanA->id]);

        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-workplan-dashboard']);

        Livewire::actingAs($adminB)
            ->test(WorkplanDashboard::class)
            ->assertViewHas('total', 0);
    }
}
