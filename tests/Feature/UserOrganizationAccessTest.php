<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class UserOrganizationAccessTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    public function test_user_with_active_affiliation_can_access_organization()
    {
        $organization = Organization::factory()->create();
        $user = $this->affiliatedUser('Staff', $organization);

        $this->assertTrue($user->canAccessOrganization($organization->id));
        $this->assertTrue($user->accessibleOrganizations()->contains('id', $organization->id));
    }

    public function test_inactive_affiliation_does_not_grant_access()
    {
        $organization = Organization::factory()->create();
        $user = $this->affiliatedUser('Staff', $organization, status: 'inactive');

        $this->assertFalse($user->canAccessOrganization($organization->id));
        $this->assertCount(0, $user->accessibleOrganizations());
    }

    public function test_affiliation_with_another_organization_does_not_grant_access()
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = $this->affiliatedUser('Staff', $organizationA);

        $this->assertFalse($user->canAccessOrganization($organizationB->id));
    }

    public function test_user_without_person_record_has_no_access()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->assertNull($user->personId());
        $this->assertFalse($user->canAccessOrganization($organization->id));
        $this->assertCount(0, $user->accessibleOrganizations());
    }

    public function test_super_admin_can_access_any_organization()
    {
        $organization = Organization::factory()->create();
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->assertTrue($user->canAccessOrganization($organization->id));
        $this->assertTrue($user->accessibleOrganizations()->contains('id', $organization->id));
    }

    public function test_managed_organization_ids_require_admin_role_and_affiliation()
    {
        $organization = Organization::factory()->create();

        $admin = $this->affiliatedUser('Organization Admin', $organization);
        $this->assertTrue($admin->managedOrganizationIds()->contains($organization->id));

        $staff = $this->affiliatedUser('Staff', $organization);
        $this->assertFalse($staff->managedOrganizationIds()->contains($organization->id));

        // Admin role without any affiliation manages nothing
        $unaffiliated = User::factory()->create();
        $unaffiliated->assignRole(Role::findOrCreate('Organization Admin', 'web'));
        $this->assertCount(0, $unaffiliated->managedOrganizationIds());
    }

    public function test_org_access_middleware_redirects_user_without_access()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/organization-units');

        $response->assertStatus(302);
    }
}
