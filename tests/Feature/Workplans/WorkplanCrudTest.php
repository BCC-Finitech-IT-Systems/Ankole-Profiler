<?php

namespace Tests\Feature\Workplans;

use App\Livewire\Workplans\WorkplansManagement;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Workplan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class WorkplanCrudTest extends TestCase
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

    private function orgAdmin(array $permissions = ['view-workplans', 'create-workplans', 'edit-workplans', 'archive-workplans'])
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, $permissions);
    }

    public function test_admin_can_create_a_workplan()
    {
        $admin = $this->orgAdmin();

        Livewire::actingAs($admin)
            ->test(WorkplansManagement::class)
            ->call('create')
            ->set('department_id', $this->department->id)
            ->set('year', 2026)
            ->set('title', 'FY2026 Workplan')
            ->call('save')
            ->assertHasNoErrors();

        $workplan = Workplan::where('department_id', $this->department->id)->where('year', 2026)->first();
        $this->assertNotNull($workplan);
        $this->assertEquals('draft', $workplan->status);
        $this->assertEquals(1, $workplan->version_number);
        $this->assertEquals($this->organization->id, $workplan->organization_id);
    }

    public function test_duplicate_department_and_year_is_rejected()
    {
        Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id, 'year' => 2026]);

        $admin = $this->orgAdmin();

        Livewire::actingAs($admin)
            ->test(WorkplansManagement::class)
            ->call('create')
            ->set('department_id', $this->department->id)
            ->set('year', 2026)
            ->call('save')
            ->assertStatus(422);
    }

    public function test_add_workplan_button_hidden_without_create_permission()
    {
        $viewer = $this->orgAdmin(['view-workplans']);

        $response = $this->actingAs($viewer)->get(route('workplans.index'));

        $response->assertOk();
        $response->assertDontSee('Add Workplan');
    }

    public function test_add_workplan_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->orgAdmin();

        $response = $this->actingAs($admin)->get(route('workplans.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['wire:id', 'Add Workplan'], false);
    }

    public function test_outsider_department_cannot_edit()
    {
        $workplan = Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id]);

        $outsider = $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, ['view-workplans', 'edit-workplans']);

        Livewire::actingAs($outsider)
            ->test(\App\Livewire\Workplans\WorkplanDetail::class, ['workplan' => $workplan])
            ->assertForbidden();
    }

    public function test_cancel_requires_permission_and_scope()
    {
        $admin = $this->orgAdmin();
        $workplan = Workplan::factory()->create(['department_id' => $this->department->id, 'organization_id' => $this->organization->id]);

        Livewire::actingAs($admin)
            ->test(WorkplansManagement::class)
            ->call('confirmArchive', $workplan->id)
            ->call('archive')
            ->assertHasNoErrors();

        $this->assertEquals('cancelled', $workplan->fresh()->status);
    }
}
