<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentsManagement;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentCrudTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function admin(array $permissions = ['view-assignments', 'create-assignments', 'edit-assignments'])
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, $permissions);
    }

    public function test_admin_can_create_an_assignment()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(AssignmentsManagement::class)
            ->call('create')
            ->set('title', 'Prepare audit response')
            ->set('organization_id', $this->organization->id)
            ->set('department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors();

        $assignment = Assignment::where('title', 'Prepare audit response')->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('not_started', $assignment->status);
        $this->assertEquals($this->organization->id, $assignment->organization_id);
    }

    public function test_add_assignment_button_hidden_without_create_permission()
    {
        $viewer = $this->admin(['view-assignments']);

        $response = $this->actingAs($viewer)->get(route('assignments.index'));

        $response->assertOk();
        $response->assertDontSee('Add Assignment');
    }

    public function test_add_assignment_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('assignments.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['wire:id', 'Add Assignment'], false);
    }

    public function test_cross_organization_assignee_is_rejected_on_save()
    {
        $admin = $this->admin();

        $otherOrg = Organization::factory()->create();
        $foreignPerson = $this->affiliatedUser('Person', $otherOrg, null, [])->person;

        $component = Livewire::actingAs($admin)
            ->test(AssignmentsManagement::class)
            ->call('create')
            ->set('title', 'Cross org test')
            ->set('organization_id', $this->organization->id)
            ->set('responsible_person_id', $foreignPerson->id)
            ->call('save');

        $component->assertHasErrors(['responsible_person_id']);
        $this->assertDatabaseMissing('assignments', ['title' => 'Cross org test']);
    }

    public function test_outsider_department_cannot_edit()
    {
        $assignment = Assignment::factory()->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);

        $outsider = $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, ['view-assignments', 'edit-assignments']);

        Livewire::actingAs($outsider)
            ->test(\App\Livewire\Assignments\AssignmentDetail::class, ['assignment' => $assignment])
            ->assertForbidden();
    }
}
