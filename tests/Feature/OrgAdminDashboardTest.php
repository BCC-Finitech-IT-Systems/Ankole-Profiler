<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class OrgAdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    public function test_admin_dashboard_renders_with_people_affiliated_to_institutions()
    {
        Role::findOrCreate('Project Head', 'web');
        $diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $school = Organization::factory()->create(['is_super' => false, 'legal_name' => 'St James SS']);

        $person = Person::factory()->create(['given_name' => 'Mary', 'family_name' => 'Kansiime']);
        PersonAffiliation::create([
            'person_id' => $person->id,
            'organization_id' => $school->id,
            'role_type' => 'STAFF',
            'status' => 'active',
        ]);

        $admin = $this->affiliatedUser('Organization Admin', $diocese);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('St James SS')
            ->assertSee('Mary Kansiime');
    }
}
