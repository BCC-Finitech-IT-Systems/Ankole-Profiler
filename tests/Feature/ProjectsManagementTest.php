<?php

namespace Tests\Feature;

use App\Livewire\Projects\ProjectsManagement;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

/**
 * Regression: the "Add Project" button was rendered via x-slot="header",
 * which Livewire's page-layout mechanism renders outside the component's
 * wire:id-tracked root — silently stripping the wire:click binding, so the
 * button was visible but did nothing. See resources/views/livewire/projects/
 * projects-management.blade.php.
 */
class ProjectsManagementTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private function superAdmin(): User
    {
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_add_project_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('projects.manage'));

        $response->assertOk();
        // wire:id is injected onto the component's own root element, which
        // must now be an ancestor of the button rather than a sibling.
        $response->assertSeeInOrder(['wire:id', 'Add Project'], false);
    }

    public function test_add_project_button_is_hidden_without_create_permission()
    {
        $organization = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create(['organization_id' => $organization->id]);

        $user = $this->affiliatedUser('Organization Admin', $organization, $department, ['view-projects']);

        $response = $this->actingAs($user)->get(route('projects.manage'));

        $response->assertOk();
        $response->assertDontSee('Add Project');
    }

    public function test_create_opens_the_project_modal()
    {
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(ProjectsManagement::class)
            ->assertSet('showModal', false)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('editingId', null);
    }

    public function test_department_manager_can_create_project_in_a_managed_department()
    {
        $organization = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create(['organization_id' => $organization->id]);

        $manager = $this->affiliatedUser(
            'Department Manager',
            $organization,
            $department,
            ['view-projects', 'create-projects']
        );

        Livewire::actingAs($manager)
            ->test(ProjectsManagement::class)
            ->call('create')
            ->set('name', 'New Diocese Project')
            ->set('department_id', $department->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('projects', [
            'name' => 'New Diocese Project',
            'department_id' => $department->id,
        ]);
    }

    public function test_department_manager_cannot_create_project_in_an_unmanaged_department()
    {
        $organization = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $managedDepartment = Department::factory()->create(['organization_id' => $organization->id]);
        $foreignDepartment = Department::factory()->create();

        $manager = $this->affiliatedUser(
            'Department Manager',
            $organization,
            $managedDepartment,
            ['view-projects', 'create-projects']
        );

        Livewire::actingAs($manager)
            ->test(ProjectsManagement::class)
            ->call('create')
            ->set('name', 'Cross Org Project')
            ->set('department_id', $foreignDepartment->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', ['name' => 'Cross Org Project']);
    }

    public function test_organization_unit_from_a_different_department_is_rejected()
    {
        $admin = $this->superAdmin();

        $organization = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $otherDepartment = Department::factory()->create();
        $foreignUnit = OrganizationUnit::factory()->create(['department_id' => $otherDepartment->id]);

        Livewire::actingAs($admin)
            ->test(ProjectsManagement::class)
            ->call('create')
            ->set('name', 'Unit Mismatch Project')
            ->set('department_id', $department->id)
            ->set('organization_unit_id', $foreignUnit->id)
            ->call('save')
            ->assertHasErrors(['organization_unit_id']);

        $this->assertDatabaseMissing('projects', ['name' => 'Unit Mismatch Project']);
    }
}
