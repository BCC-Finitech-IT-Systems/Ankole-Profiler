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

/**
 * Regression tests: person search/suggestions/export must not leak persons
 * from organizations the user has no active affiliation with.
 */
class PersonSearchScopingTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $orgA;
    private Organization $orgB;
    private User $orgAUser;
    private Person $personInA;
    private Person $personInB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->orgAUser = $this->affiliatedUser('Staff', $this->orgA);

        $this->personInA = $this->personAffiliatedWith($this->orgA, 'Alice', 'Insider');
        $this->personInB = $this->personAffiliatedWith($this->orgB, 'Bob', 'Outsider');
    }

    private function personAffiliatedWith(Organization $organization, string $givenName, string $familyName): Person
    {
        $person = Person::factory()->create([
            'given_name' => $givenName,
            'family_name' => $familyName,
            'status' => 'active',
        ]);

        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        return $person;
    }

    private function actingAsOrgAUser()
    {
        return $this->actingAs($this->orgAUser)
            ->withSession(['current_organization_id' => $this->orgA->id]);
    }

    public function test_search_api_only_returns_persons_from_current_organization()
    {
        $response = $this->actingAsOrgAUser()->getJson(route('persons.search.api'));

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('given_name');
        $this->assertTrue($names->contains('Alice'));
        $this->assertFalse($names->contains('Bob'));
    }

    public function test_search_api_ignores_requested_foreign_organization_filter()
    {
        $response = $this->actingAsOrgAUser()
            ->getJson(route('persons.search.api', ['OrganizationId' => $this->orgB->id]));

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('given_name');
        $this->assertFalse($names->contains('Bob'));
    }

    public function test_suggestions_do_not_leak_other_organizations()
    {
        $response = $this->actingAsOrgAUser()
            ->getJson(route('persons.search.suggestions', ['term' => 'Bob']));

        $response->assertOk();
        $response->assertJsonCount(0);

        $response = $this->actingAsOrgAUser()
            ->getJson(route('persons.search.suggestions', ['term' => 'Alice']));

        $response->assertOk();
        $this->assertNotEmpty($response->json());
    }

    public function test_export_of_selected_persons_excludes_foreign_persons()
    {
        // Selecting only a foreign person leaves nothing to export.
        $response = $this->actingAsOrgAUser()
            ->from(route('persons.search'))
            ->post(route('persons.search.export'), [
                'selectedPersons' => [$this->personInB->id],
            ]);

        $response->assertRedirect(route('persons.search'));
        $response->assertSessionHas('error');
    }

    public function test_export_of_own_organization_persons_succeeds()
    {
        $response = $this->actingAsOrgAUser()
            ->post(route('persons.search.export'), [
                'selectedPersons' => [$this->personInA->id],
            ]);

        $response->assertOk();
        $response->assertDownload();
    }

    public function test_super_admin_searches_across_all_organizations()
    {
        Role::findOrCreate('Super Admin', 'web');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $response = $this->actingAs($superAdmin)
            ->withSession(['current_organization_id' => $this->orgA->id])
            ->getJson(route('persons.search.api'));

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('given_name');
        $this->assertTrue($names->contains('Alice'));
        $this->assertTrue($names->contains('Bob'));
    }
}
