<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDetail;
use App\Models\Assignment;
use App\Models\AssignmentProgressUpdate;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentProgressTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->assignment = Assignment::factory()->create([
            'organization_id' => $this->organization->id, 'status' => 'in_progress',
        ]);
    }

    private function makeAssignee(): array
    {
        Role::findOrCreate('Person', 'web');
        $user = User::factory()->create();
        $user->assignRole('Person');
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('view-assignments', 'web'));
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('report-assignment-progress', 'web'));
        $person = Person::factory()->create(['user_id' => $user->id]);

        return [$user->fresh(), $person];
    }

    public function test_the_lead_can_report_progress_on_their_own_assignment()
    {
        [$user, $person] = $this->makeAssignee();
        $this->assignment->update(['responsible_person_id' => $person->id]);

        Livewire::actingAs($user)
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('openProgressForm')
            ->set('progress_percent_complete', 50)
            ->set('progressStatus', 'in_progress')
            ->set('notes', 'Halfway there')
            ->call('recordProgress')
            ->assertHasNoErrors();

        $this->assertEquals(1, AssignmentProgressUpdate::where('assignment_id', $this->assignment->id)->count());
        $this->assignment->refresh();
        $this->assertEquals(50, $this->assignment->percent_complete);
    }

    public function test_an_unrelated_person_cannot_report_progress()
    {
        [$user, $person] = $this->makeAssignee();
        // Not attached as lead or support — no relation to the assignment,
        // so even viewing it is denied (mount() itself throws forbidden).

        Livewire::actingAs($user)
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->assertForbidden();
    }

    public function test_support_assignee_can_report_progress()
    {
        [$user, $person] = $this->makeAssignee();
        $this->assignment->supportPeople()->attach($person->id, ['role' => 'support']);

        Livewire::actingAs($user)
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('openProgressForm')
            ->set('progress_percent_complete', 30)
            ->call('recordProgress')
            ->assertHasNoErrors();

        $this->assertEquals(30, $this->assignment->fresh()->percent_complete);
    }

    public function test_progress_update_applies_a_revised_due_date()
    {
        [$user, $person] = $this->makeAssignee();
        $this->assignment->update(['responsible_person_id' => $person->id, 'due_date' => now()->addDays(5)]);

        $newDate = now()->addDays(20)->toDateString();

        Livewire::actingAs($user)
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('openProgressForm')
            ->set('progress_percent_complete', 10)
            ->set('revised_due_date', $newDate)
            ->call('recordProgress')
            ->assertHasNoErrors();

        $this->assertEquals($newDate, $this->assignment->fresh()->revised_due_date->toDateString());
    }
}
