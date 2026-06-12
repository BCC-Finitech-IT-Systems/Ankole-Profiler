<?php

namespace Tests\Feature;

use App\Livewire\Organizations\ManageUnitMembers;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\RoleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class ManageUnitMembersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Department $department;
    private OrganizationUnit $unit;
    private RoleType $memberRoleType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->create(['is_super' => false]);
        $this->department = Department::factory()->create(['organization_id' => $this->diocese->id]);
        $this->unit = OrganizationUnit::factory()->create([
            'organization_id' => $this->diocese->id,
            'department_id'   => $this->department->id,
        ]);
        $this->memberRoleType = RoleType::factory()->create([
            'department_id' => $this->department->id,
            'code'          => 'MEMBER',
            'name'          => 'Member',
            'active'        => true,
        ]);

        Permission::findOrCreate('edit-units', 'web');
        Role::findOrCreate('Organization Admin', 'web');
    }

    public function test_unit_admin_can_add_member_with_department_role_type()
    {
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            $this->department,
            ['edit-units']
        );
        $person = Person::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageUnitMembers::class, ['unitId' => $this->unit->id])
            ->set('selectedPersonId', $person->id)
            ->set('selectedPersonName', $person->full_name)
            ->set('newRoleTypeId', $this->memberRoleType->id)
            ->call('addMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('unit_person_roles', [
            'unit_id'      => $this->unit->id,
            'person_id'    => $person->id,
            'role_type_id' => $this->memberRoleType->id,
            'role'         => 'member',
        ]);

        $this->assertDatabaseHas('person_affiliations', [
            'person_id'            => $person->id,
            'organization_unit_id' => $this->unit->id,
            'status'               => 'active',
        ]);
    }

    public function test_cross_org_manager_cannot_manage_unit()
    {
        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $admin = $this->affiliatedUser('Organization Admin', $otherDiocese, permissions: ['edit-units']);

        Livewire::actingAs($admin)
            ->test(ManageUnitMembers::class, ['unitId' => $this->unit->id])
            ->assertForbidden();
    }

    public function test_role_type_from_wrong_department_is_rejected()
    {
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            $this->department,
            ['edit-units']
        );
        $person = Person::factory()->create();

        $otherDept = Department::factory()->create(['organization_id' => $this->diocese->id]);
        $foreignRoleType = RoleType::factory()->create([
            'department_id' => $otherDept->id,
            'code'          => 'LEADER',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageUnitMembers::class, ['unitId' => $this->unit->id])
            ->set('selectedPersonId', $person->id)
            ->set('selectedPersonName', $person->full_name)
            ->set('newRoleTypeId', $foreignRoleType->id)
            ->call('addMember')
            ->assertHasErrors(['newRoleTypeId']);
    }

    public function test_user_without_edit_units_permission_cannot_manage()
    {
        // 'Person' role has no edit-units permission, so access must be denied.
        Role::findOrCreate('Person', 'web');
        $noPermUser = $this->affiliatedUser('Person', $this->diocese, $this->department);

        Livewire::actingAs($noPermUser)
            ->test(ManageUnitMembers::class, ['unitId' => $this->unit->id])
            ->assertForbidden();
    }

    public function test_department_manager_can_manage_own_department_unit()
    {
        Permission::findOrCreate('edit-units', 'web');
        Role::findOrCreate('Department Manager', 'web');

        $manager = $this->affiliatedUser(
            'Department Manager',
            $this->diocese,
            $this->department,
            ['edit-units']
        );

        Livewire::actingAs($manager)
            ->test(ManageUnitMembers::class, ['unitId' => $this->unit->id])
            ->assertOk();
    }
}
