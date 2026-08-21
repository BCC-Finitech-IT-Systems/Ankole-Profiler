<?php

namespace Tests\Feature;

use App\Livewire\Departments\DepartmentComponent;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

/**
 * The Organization Admin view of /departments renders its own
 * "My Department(s)" table, which had no Actions column at all — an org
 * admin could create departments but never edit or delete them. The Super
 * Admin table below it is hidden from them by an isOrgAdmin branch.
 */
class OrgAdminDepartmentActionsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private function orgAdminFor(Organization $organization, array $permissions): \App\Models\User
    {
        return $this->affiliatedUser('Organization Admin', $organization, null, $permissions);
    }

    public function test_org_admin_sees_edit_and_delete_actions_for_their_own_departments()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create([
            'organization_id' => $diocese->id,
            'name' => 'Audit and Assurance',
        ]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'edit-departments', 'delete-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->assertSee('Audit and Assurance')
            ->assertSeeHtml('wire:click="openEditModal(' . $department->id . ')"')
            ->assertSeeHtml('wire:click="confirmDeleteDepartment(' . $department->id . ')"');
    }

    public function test_org_admin_without_edit_or_delete_permission_sees_no_action_buttons()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create(['organization_id' => $diocese->id]);

        $admin = $this->orgAdminFor($diocese, ['view-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->assertDontSeeHtml('wire:click="openEditModal(' . $department->id . ')"')
            ->assertDontSeeHtml('wire:click="confirmDeleteDepartment(' . $department->id . ')"');
    }

    public function test_org_admin_can_edit_and_delete_their_own_department()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create([
            'organization_id' => $diocese->id,
            'name' => 'Human Resource',
        ]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'edit-departments', 'delete-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('openEditModal', $department->id)
            ->assertSet('editingDepartmentId', $department->id)
            ->call('confirmDeleteDepartment', $department->id)
            ->call('deleteDepartment');

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    /**
     * departments.deleted_at exists, but the component used forceDelete(),
     * which bypasses it and fires the ON DELETE CASCADE on projects,
     * workplans and department_sub_categories. Deleting a department must
     * not silently destroy its projects.
     */
    public function test_deleting_a_department_with_projects_is_refused_and_leaves_the_projects_intact()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $department = Department::factory()->create(['organization_id' => $diocese->id]);
        $project = Project::create([
            'name' => 'Attached Project',
            'department_id' => $department->id,
        ]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'delete-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('confirmDeleteDepartment', $department->id)
            ->call('deleteDepartment');

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_org_admin_cannot_touch_a_department_in_another_organization()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $foreignDepartment = Department::factory()->create();

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'edit-departments', 'delete-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('confirmDeleteDepartment', $foreignDepartment->id)
            ->assertForbidden();

        $this->assertDatabaseHas('departments', ['id' => $foreignDepartment->id, 'deleted_at' => null]);
    }
}
