<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDetail;
use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentReviewTest extends TestCase
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
            'organization_id' => $this->organization->id, 'status' => 'awaiting_review',
        ]);
    }

    private function reviewer()
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-assignments', 'review-assignments']);
    }

    public function test_accept_closes_the_assignment_as_completed()
    {
        Livewire::actingAs($this->reviewer())
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('accept')
            ->assertHasNoErrors();

        $this->assignment->refresh();
        $this->assertEquals('completed', $this->assignment->status);
        $this->assertNotNull($this->assignment->closed_at);
    }

    public function test_return_requires_a_reason_and_reopens_the_assignment()
    {
        Livewire::actingAs($this->reviewer())
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('returnForRevision')
            ->assertHasErrors(['review_comment']);

        Livewire::actingAs($this->reviewer())
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->set('review_comment', 'Please add more detail.')
            ->call('returnForRevision')
            ->assertHasNoErrors();

        $this->assignment->refresh();
        $this->assertEquals('in_progress', $this->assignment->status);
        $this->assertEquals('Please add more detail.', $this->assignment->review_comment);
    }

    public function test_close_requires_a_reason_and_sets_the_chosen_status()
    {
        Livewire::actingAs($this->reviewer())
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->set('closeStatus', 'deferred')
            ->set('review_comment', 'Deprioritized this quarter.')
            ->call('close')
            ->assertHasNoErrors();

        $this->assignment->refresh();
        $this->assertEquals('deferred', $this->assignment->status);
        $this->assertNotNull($this->assignment->closed_at);
    }

    public function test_the_assignee_cannot_review_their_own_assignment()
    {
        Role::findOrCreate('Person', 'web');
        $user = User::factory()->create();
        $user->assignRole('Person');
        $user->givePermissionTo(Permission::findOrCreate('view-assignments', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('review-assignments', 'web'));
        $person = Person::factory()->create(['user_id' => $user->id]);
        $this->assignment->update(['responsible_person_id' => $person->id]);

        Livewire::actingAs($user->fresh())
            ->test(AssignmentDetail::class, ['assignment' => $this->assignment])
            ->call('accept')
            ->assertForbidden();
    }
}
