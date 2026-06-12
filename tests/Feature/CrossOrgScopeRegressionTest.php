<?php

namespace Tests\Feature;

use App\Livewire\Person\CreatePersonsComponent;
use App\Models\Department;
use App\Models\DepartmentSubCategory;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

/**
 * Regressions for cross-organization scope bypasses:
 * - CreatePersonsComponent accepted any existing organization_id from an
 *   Org Admin instead of restricting to their department's scope.
 * - The /projects/{project}/persons route only checked the view-projects
 *   permission, not whether the user could view that project's department.
 */
class CrossOrgScopeRegressionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    /**
     * @return array{0: User, 1: Organization, 2: Organization}
     */
    private function buildOrgAdminWithSchoolScope(): array
    {
        Role::findOrCreate('Person', 'web');

        $treeRoot = Organization::factory()->create([
            'is_super' => true,
            'organization_type' => 'super',
        ]);
        $department = Department::factory()->create([
            'organization_id' => $treeRoot->id,
            'name' => 'Education',
        ]);
        DepartmentSubCategory::create([
            'department_id' => $department->id,
            'name' => 'school',
            'is_active' => true,
        ]);

        $inTreeSchool = Organization::factory()->create([
            'category' => 'school',
            'organization_type' => 'branch',
            'parent_organization_id' => $treeRoot->id,
        ]);
        $foreignSchool = Organization::factory()->create([
            'category' => 'school',
            'organization_type' => 'branch',
        ]);

        $admin = $this->affiliatedUser('Organization Admin', $treeRoot, $department);

        return [$admin, $inTreeSchool, $foreignSchool];
    }

    private function fillPersonForm($component, Organization $organization, string $email)
    {
        return $component
            ->set('form.given_name', 'Test')
            ->set('form.family_name', 'Person')
            ->set('form.date_of_birth', '1990-01-01')
            ->set('form.gender', 'Male')
            ->set('form.phone', '+25670' . random_int(1000000, 9999999))
            ->set('form.email', $email)
            ->set('form.address', '12 Test Lane')
            ->set('form.country', 'Uganda')
            ->set('form.district', 'Mbarara')
            ->set('form.city', 'Mbarara')
            ->set('form.role_title', 'Staff')
            ->set('form.organization_id', $organization->id);
    }

    public function test_org_admin_cannot_create_person_under_out_of_scope_organization()
    {
        [$admin, $inTreeSchool, $foreignSchool] = $this->buildOrgAdminWithSchoolScope();

        $component = Livewire::actingAs($admin)->test(CreatePersonsComponent::class);
        $this->fillPersonForm($component, $foreignSchool, 'outofscope@example.com')
            ->call('submit');

        $this->assertDatabaseMissing('person_affiliations', [
            'organization_id' => $foreignSchool->id,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'outofscope@example.com']);
    }

    public function test_org_admin_can_create_person_under_in_scope_organization()
    {
        [$admin, $inTreeSchool] = $this->buildOrgAdminWithSchoolScope();

        $component = Livewire::actingAs($admin)->test(CreatePersonsComponent::class);
        $this->fillPersonForm($component, $inTreeSchool, 'inscope@example.com')
            ->call('submit');

        $this->assertDatabaseHas('person_affiliations', [
            'organization_id' => $inTreeSchool->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'inscope@example.com']);
    }

    public function test_project_persons_route_is_scoped_to_allowed_departments()
    {
        $org = Organization::factory()->create();
        $department = Department::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'department_id' => $department->id,
            'name' => 'Scoped Project',
        ]);

        $outsider = $this->affiliatedUser(
            'Staff',
            Organization::factory()->create(),
            permissions: ['view-projects']
        );

        $this->actingAs($outsider)
            ->get(route('projects.persons', $project->id))
            ->assertForbidden();
    }

    public function test_department_admin_can_view_project_persons()
    {
        $org = Organization::factory()->create();
        $department = Department::factory()->create(['organization_id' => $org->id]);
        $project = Project::create([
            'department_id' => $department->id,
            'name' => 'Scoped Project',
        ]);

        $deptAdmin = $this->affiliatedUser('Staff', $org, $department, permissions: ['view-projects']);
        $department->update(['admin_user_id' => $deptAdmin->id]);

        $this->actingAs($deptAdmin)
            ->get(route('projects.persons', $project->id))
            ->assertOk();
    }
}
