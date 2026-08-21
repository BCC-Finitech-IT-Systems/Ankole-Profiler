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

    /**
     * The organization dropdown listed only the rows the admin was directly
     * affiliated with, so a diocese admin saw the diocese and nothing else —
     * no way to put a department on an institution beneath it.
     */
    public function test_organization_dropdown_offers_institutions_under_the_diocese()
    {
        $diocese = Organization::factory()->create([
            'is_super' => true,
            'organization_type' => 'super',
            'legal_name' => 'Ankole Diocese',
        ]);
        $institution = Organization::factory()->create([
            'is_super' => false,
            'parent_organization_id' => $diocese->id,
            'legal_name' => 'Akarungu Primary School',
            'category' => 'Primary School',
        ]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'create-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('openCreateModal')
            ->assertSee('Ankole Diocese')
            ->assertSee($institution->legal_name);
    }

    public function test_org_admin_can_create_a_department_on_an_institution_under_the_diocese()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $institution = Organization::factory()->create([
            'is_super' => false,
            'parent_organization_id' => $diocese->id,
            'category' => 'Primary School',
        ]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'create-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('openCreateModal')
            ->set('createForm.organization_id', $institution->id)
            ->set('createForm.name', 'School Health')
            ->call('createDepartment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'name' => 'School Health',
            'organization_id' => $institution->id,
        ]);
    }

    public function test_org_admin_still_cannot_create_a_department_outside_their_tree()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $foreign = Organization::factory()->create(['is_super' => false]);

        $admin = $this->orgAdminFor($diocese, ['view-departments', 'create-departments']);

        Livewire::actingAs($admin)
            ->test(DepartmentComponent::class)
            ->call('openCreateModal')
            ->set('createForm.organization_id', $foreign->id)
            ->set('createForm.name', 'Smuggled Department')
            ->call('createDepartment')
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['name' => 'Smuggled Department']);
    }

    /**
     * The matching-institutions list is no longer a permanent section on the
     * page; it opens from the count on the department row.
     */
    public function test_matching_institutions_open_from_the_row_count_rather_than_inline()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        Organization::factory()->create([
            'is_super' => false,
            'parent_organization_id' => $diocese->id,
            'legal_name' => 'Akarungu Primary School',
            'category' => 'Primary School',
        ]);
        $department = Department::factory()->create([
            'organization_id' => $diocese->id,
            'name' => 'Education',
        ]);
        $department->subCategories()->create(['name' => 'Primary']);

        $component = Livewire::actingAs(
            $this->orgAdminFor($diocese, ['view-departments'])
        )->test(DepartmentComponent::class);

        // Not listed inline...
        $component->assertDontSee('Akarungu Primary School')
            ->assertSeeHtml('wire:click="showMatchingOrganizations(' . $department->id . ')"');

        // ...but reachable from the count.
        $component->call('showMatchingOrganizations', $department->id)
            ->assertSee('Akarungu Primary School');
    }

    public function test_matching_institutions_cannot_be_opened_for_a_foreign_department()
    {
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $foreignDepartment = Department::factory()->create();

        Livewire::actingAs($this->orgAdminFor($diocese, ['view-departments']))
            ->test(DepartmentComponent::class)
            ->call('showMatchingOrganizations', $foreignDepartment->id)
            ->assertForbidden();
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
