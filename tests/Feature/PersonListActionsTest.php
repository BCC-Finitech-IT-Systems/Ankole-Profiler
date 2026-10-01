<?php

namespace Tests\Feature;

use App\Livewire\Person\PersonList;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

/**
 * The Edit and Add Affiliation row actions on the All Persons list, for a
 * diocese-level Organization Admin (no department affiliation).
 */
class PersonListActionsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Organization $school;
    private Organization $outsider;
    private User $admin;
    private Person $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->create(['is_super' => true, 'organization_type' => 'super']);
        $this->school = Organization::factory()->create(['is_super' => false, 'parent_organization_id' => $this->diocese->id]);
        $this->outsider = Organization::factory()->create(['is_super' => false]);
        $this->admin = $this->affiliatedUser('Organization Admin', $this->diocese);

        $this->teacher = $this->personAt($this->school);
    }

    private function personAt(Organization $organization): Person
    {
        $person = Person::factory()->create();
        PersonAffiliation::factory()->create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'department_id' => null,
            'role_type' => 'STAFF',
            'status' => 'active',
        ]);

        return $person;
    }

    public function test_edit_opens_for_a_person_in_a_managed_organization()
    {
        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('editPerson', $this->teacher->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editPersonId', $this->teacher->id);
    }

    public function test_edit_is_refused_for_a_person_outside_managed_organizations()
    {
        $stranger = $this->personAt($this->outsider);

        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('editPerson', $stranger->id)
            ->assertSet('showEditModal', false)
            ->assertDispatched('alert');
    }

    public function test_update_is_refused_when_the_edited_id_is_swapped_for_an_outsider()
    {
        $stranger = $this->personAt($this->outsider);

        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('editPerson', $this->teacher->id)
            ->set('editPersonId', $stranger->id)
            ->set('editPersonData.given_name', 'Hijacked')
            ->call('updatePerson');

        $this->assertNotSame('Hijacked', $stranger->fresh()->given_name);
    }

    public function test_add_affiliation_creates_an_active_affiliation()
    {
        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('openAffiliationModal', $this->teacher->id)
            ->assertSet('showAffiliationModal', true)
            ->set('affiliationData.organization_id', $this->diocese->id)
            ->set('affiliationData.role_type', 'VOLUNTEER')
            ->set('affiliationData.role_title', 'Choir Leader')
            ->call('saveAffiliation')
            ->assertHasNoErrors()
            ->assertSet('showAffiliationModal', false)
            ->assertDispatched('alert');

        $affiliation = PersonAffiliation::where('person_id', $this->teacher->id)
            ->where('organization_id', $this->diocese->id)
            ->firstOrFail();
        $this->assertSame('VOLUNTEER', $affiliation->role_type);
        $this->assertSame('Choir Leader', $affiliation->role_title);
        $this->assertSame('active', $affiliation->status);
    }

    public function test_add_affiliation_rejects_an_organization_outside_scope()
    {
        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('openAffiliationModal', $this->teacher->id)
            ->set('affiliationData.organization_id', $this->outsider->id)
            ->set('affiliationData.role_title', 'Teacher')
            ->call('saveAffiliation')
            ->assertHasErrors(['affiliationData.organization_id']);

        $this->assertFalse(
            PersonAffiliation::where('person_id', $this->teacher->id)->where('organization_id', $this->outsider->id)->exists()
        );
    }

    public function test_add_affiliation_rejects_a_duplicate_role_at_the_same_organization()
    {
        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('openAffiliationModal', $this->teacher->id)
            ->set('affiliationData.organization_id', $this->school->id)
            ->set('affiliationData.role_type', 'STAFF')
            ->set('affiliationData.role_title', 'Teacher')
            ->call('saveAffiliation')
            ->assertHasErrors(['affiliationData.organization_id']);

        $this->assertSame(1, PersonAffiliation::where('person_id', $this->teacher->id)->count());
    }

    public function test_add_affiliation_is_refused_for_a_person_outside_scope()
    {
        $stranger = $this->personAt($this->outsider);

        Livewire::actingAs($this->admin)
            ->test(PersonList::class)
            ->call('openAffiliationModal', $stranger->id)
            ->assertSet('showAffiliationModal', false)
            ->assertDispatched('alert');
    }
}
