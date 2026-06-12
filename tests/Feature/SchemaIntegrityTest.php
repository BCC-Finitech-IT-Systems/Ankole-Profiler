<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\PersonAffiliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliation_status_accepts_pending_and_rejected()
    {
        $organization = Organization::factory()->create();

        foreach (['pending', 'rejected'] as $status) {
            $affiliation = PersonAffiliation::factory()->create([
                'organization_id' => $organization->id,
                'status' => $status,
            ]);
            $this->assertSame($status, $affiliation->fresh()->status);
        }
    }

    public function test_duplicate_active_unit_affiliation_is_rejected()
    {
        $organization = Organization::factory()->create();
        $person = Person::factory()->create();
        $unit = OrganizationUnit::create([
            'organization_id' => $organization->id,
            'name' => 'Choir',
            'code' => 'CHOIR-1',
            'is_active' => true,
        ]);

        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'organization_unit_id' => $unit->id,
            'status' => 'active',
        ]);

        $this->expectException(\DomainException::class);

        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'organization_unit_id' => $unit->id,
            'status' => 'active',
        ]);
    }

    public function test_inactive_duplicate_unit_affiliations_are_allowed()
    {
        $organization = Organization::factory()->create();
        $person = Person::factory()->create();
        $unit = OrganizationUnit::create([
            'organization_id' => $organization->id,
            'name' => 'Ushers',
            'code' => 'USH-1',
            'is_active' => true,
        ]);

        // role_type varies because (person, organization, role_type) already
        // carries its own unique constraint.
        foreach (['inactive' => 'STAFF', 'inactive ' => 'MEMBER', 'active' => 'LEADER'] as $status => $roleType) {
            PersonAffiliation::factory()->create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'organization_unit_id' => $unit->id,
                'status' => trim($status),
                'role_type' => $roleType,
            ]);
        }

        $this->assertSame(3, PersonAffiliation::where('person_id', $person->id)->count());
    }
}
