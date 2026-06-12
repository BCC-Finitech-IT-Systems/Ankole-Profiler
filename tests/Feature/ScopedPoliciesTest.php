<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class ScopedPoliciesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private function makeUnit(Organization $organization, ?Department $department = null): OrganizationUnit
    {
        return OrganizationUnit::create([
            'organization_id' => $organization->id,
            'department_id' => $department?->id,
            'name' => 'Unit ' . uniqid(),
            'code' => 'U-' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_org_admin_can_manage_units_only_in_their_organization()
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->affiliatedUser('Organization Admin', $orgA, permissions: ['edit-units']);

        $this->assertTrue($admin->can('manage', $this->makeUnit($orgA)));
        $this->assertFalse($admin->can('manage', $this->makeUnit($orgB)));
    }

    public function test_department_manager_is_limited_to_their_department()
    {
        $org = Organization::factory()->create();
        $deptA = Department::create(['organization_id' => $org->id, 'name' => 'Education']);
        $deptB = Department::create(['organization_id' => $org->id, 'name' => 'Health']);
        $manager = $this->affiliatedUser('Department Manager', $org, $deptA, permissions: ['edit-units']);

        $this->assertTrue($manager->can('manage', $this->makeUnit($org, $deptA)));
        $this->assertFalse($manager->can('manage', $this->makeUnit($org, $deptB)));
    }

    public function test_permission_without_affiliation_grants_nothing()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $role = Role::findOrCreate('Organization Admin', 'web');
        $role->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('edit-units', 'web'));
        $user->assignRole($role);

        $this->assertFalse($user->can('manage', $this->makeUnit($org)));
    }

    public function test_unit_application_approval_is_scoped_to_organization()
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->affiliatedUser('Organization Admin', $orgA, permissions: ['approve-unit-membership']);

        $applicationA = OrganizationUnitApplication::create([
            'organization_id' => $orgA->id,
            'unit_id' => $this->makeUnit($orgA)->id,
            'person_id' => Person::factory()->create()->id,
            'status' => 'pending',
        ]);
        $applicationB = OrganizationUnitApplication::create([
            'organization_id' => $orgB->id,
            'unit_id' => $this->makeUnit($orgB)->id,
            'person_id' => Person::factory()->create()->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($admin->can('approve', $applicationA));
        $this->assertFalse($admin->can('approve', $applicationB));
    }

    public function test_membership_approval_is_scoped_to_organization()
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->affiliatedUser('Organization Admin', $orgA, permissions: ['approve-organization-membership']);

        $pendingA = PersonAffiliation::factory()->create(['organization_id' => $orgA->id, 'status' => 'pending']);
        $pendingB = PersonAffiliation::factory()->create(['organization_id' => $orgB->id, 'status' => 'pending']);

        $this->assertTrue($admin->can('approveMembership', $pendingA));
        $this->assertFalse($admin->can('approveMembership', $pendingB));
    }

    public function test_super_admin_passes_all_scoped_checks()
    {
        $org = Organization::factory()->create();
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->assertTrue($user->can('manage', $this->makeUnit($org)));
    }
}
