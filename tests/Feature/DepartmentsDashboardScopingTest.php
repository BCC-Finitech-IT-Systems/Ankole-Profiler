<?php

namespace Tests\Feature;

use App\Livewire\Departments\DepartmentsDashboard;
use App\Models\Department;
use App\Models\DepartmentSubCategory;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression: sub-category matching on the departments dashboard must stay
 * inside the selected department's own organization tree. Matching by
 * category name alone leaked projects and organizations from unrelated
 * trees (e.g. any 'school' organization anywhere in the system).
 */
class DepartmentsDashboardScopingTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Organization $inTreeSchool;
    private Organization $foreignSchool;
    private Project $inTreeProject;
    private Project $foreignProject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Super Admin', 'web');

        $treeRoot = Organization::factory()->create([
            'is_super' => true,
            'organization_type' => 'super',
        ]);
        $this->department = Department::factory()->create([
            'organization_id' => $treeRoot->id,
            'name' => 'Education',
        ]);
        DepartmentSubCategory::create([
            'department_id' => $this->department->id,
            'name' => 'school',
            'is_active' => true,
        ]);

        // In-tree: a school under the department's own organization.
        $this->inTreeSchool = Organization::factory()->create([
            'category' => 'school',
            'organization_type' => 'branch',
            'parent_organization_id' => $treeRoot->id,
        ]);
        $inTreeDept = Department::factory()->create(['organization_id' => $this->inTreeSchool->id]);
        $this->inTreeProject = Project::create([
            'department_id' => $inTreeDept->id,
            'name' => 'In Tree School Project',
        ]);

        // Foreign: same category, but a different organization tree.
        $this->foreignSchool = Organization::factory()->create([
            'category' => 'school',
            'organization_type' => 'branch',
        ]);
        $foreignDept = Department::factory()->create(['organization_id' => $this->foreignSchool->id]);
        $this->foreignProject = Project::create([
            'department_id' => $foreignDept->id,
            'name' => 'Foreign School Project',
        ]);
    }

    public function test_sub_category_projects_are_scoped_to_own_organization_tree()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(DepartmentsDashboard::class)
            ->set('activeDepartmentId', $this->department->id)
            ->assertViewHas('selectedDepartmentProjects', function ($projects) {
                return $projects->contains('id', $this->inTreeProject->id)
                    && !$projects->contains('id', $this->foreignProject->id);
            });
    }

    public function test_sub_category_organizations_are_scoped_to_own_organization_tree()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(DepartmentsDashboard::class)
            ->set('activeDepartmentId', $this->department->id)
            ->assertViewHas('departmentOrganizations', function ($organizations) {
                return $organizations->contains('id', $this->inTreeSchool->id)
                    && !$organizations->contains('id', $this->foreignSchool->id);
            });
    }
}
