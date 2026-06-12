<?php

namespace Tests\Feature;

use App\Livewire\Organizations\ReviewUnitApplications;
use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitApplication;
use App\Models\Person;
use App\Models\RoleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class ReviewUnitApplicationsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Department $department;
    private OrganizationUnit $unit;
    private OrganizationUnitApplication $application;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('approve-unit-membership', 'web');
        Permission::findOrCreate('bulk-approve-unit-membership', 'web');
        Role::findOrCreate('Organization Admin', 'web');

        $this->diocese = Organization::factory()->create(['is_super' => false]);
        $this->department = Department::factory()->create(['organization_id' => $this->diocese->id]);
        $this->unit = OrganizationUnit::factory()->create([
            'organization_id' => $this->diocese->id,
            'department_id'   => $this->department->id,
        ]);
        $person = Person::factory()->create();
        $this->application = OrganizationUnitApplication::create([
            'organization_id' => $this->diocese->id,
            'unit_id'         => $this->unit->id,
            'person_id'       => $person->id,
            'status'          => 'pending',
        ]);
    }

    public function test_org_admin_can_approve_application_in_own_org()
    {
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['approve-unit-membership']
        );

        Livewire::actingAs($admin)
            ->test(ReviewUnitApplications::class)
            ->call('approve', $this->application->id);

        $this->assertSame('approved', $this->application->fresh()->status);
        $this->assertDatabaseHas('unit_person_roles', [
            'unit_id'   => $this->unit->id,
            'person_id' => $this->application->person_id,
        ]);
    }

    public function test_cross_org_admin_cannot_approve()
    {
        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $otherDiocese,
            permissions: ['approve-unit-membership']
        );

        Livewire::actingAs($admin)
            ->test(ReviewUnitApplications::class)
            ->call('approve', $this->application->id)
            ->assertForbidden();

        $this->assertSame('pending', $this->application->fresh()->status);
    }

    public function test_bulk_approve_ignores_foreign_org_applications()
    {
        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $otherUnit = OrganizationUnit::factory()->create(['organization_id' => $otherDiocese->id]);
        $foreignApp = OrganizationUnitApplication::create([
            'organization_id' => $otherDiocese->id,
            'unit_id'         => $otherUnit->id,
            'person_id'       => Person::factory()->create()->id,
            'status'          => 'pending',
        ]);

        // Bulk approve requires bulk permission; per-item Gate::check requires approve permission.
        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['bulk-approve-unit-membership', 'approve-unit-membership']
        );

        Livewire::actingAs($admin)
            ->test(ReviewUnitApplications::class)
            ->set('selectedIds', [$this->application->id, $foreignApp->id])
            ->call('bulkApprove');

        // Own-org application approved; foreign one untouched.
        $this->assertSame('approved', $this->application->fresh()->status);
        $this->assertSame('pending', $foreignApp->fresh()->status);
    }

    public function test_render_scopes_applications_to_managed_orgs()
    {
        $otherDiocese = Organization::factory()->create(['is_super' => false]);
        $otherUnit = OrganizationUnit::factory()->create(['organization_id' => $otherDiocese->id]);
        OrganizationUnitApplication::create([
            'organization_id' => $otherDiocese->id,
            'unit_id'         => $otherUnit->id,
            'person_id'       => Person::factory()->create()->id,
            'status'          => 'pending',
        ]);

        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['approve-unit-membership']
        );

        Livewire::actingAs($admin)
            ->test(ReviewUnitApplications::class)
            ->assertViewHas('applications', function ($apps) {
                return $apps->every(
                    fn ($app) => $app->organization_id === $this->diocese->id
                );
            });
    }

    public function test_approval_writes_role_type_id()
    {
        RoleType::factory()->create([
            'department_id' => $this->department->id,
            'code'          => 'MEMBER',
            'name'          => 'Member',
            'active'        => true,
        ]);

        $admin = $this->affiliatedUser(
            'Organization Admin',
            $this->diocese,
            permissions: ['approve-unit-membership']
        );

        Livewire::actingAs($admin)
            ->test(ReviewUnitApplications::class)
            ->call('approve', $this->application->id);

        $role = \App\Models\UnitPersonRole::where([
            'unit_id'   => $this->unit->id,
            'person_id' => $this->application->person_id,
        ])->first();

        $this->assertNotNull($role?->role_type_id, 'role_type_id should be set on approval');
    }
}
