<?php

namespace Tests\Feature\Assignments;

use App\Livewire\Assignments\AssignmentDetail;
use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Notifications\AssignmentStatusChanged;
use App\Services\AssignmentNotificationTargets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    public function test_recipients_are_deduplicated_when_lead_and_watcher_overlap()
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        $assignment = Assignment::factory()->create([
            'organization_id' => $this->organization->id,
            'responsible_person_id' => $person->id,
        ]);
        // Same person also added as a watcher — should not double the count.
        $assignment->watchers()->attach($person->id, ['role' => 'watcher']);

        $recipients = AssignmentNotificationTargets::forAssignment($assignment->fresh());

        $this->assertCount(1, $recipients);
    }

    public function test_status_change_notifies_lead_and_watchers()
    {
        Notification::fake();

        Role::findOrCreate('Person', 'web');
        $reportPermission = Permission::findOrCreate('report-assignment-progress', 'web');
        $viewPermission = Permission::findOrCreate('view-assignments', 'web');

        $leadUser = User::factory()->create();
        $leadUser->assignRole('Person');
        $leadUser->givePermissionTo([$reportPermission, $viewPermission]);
        $leadPerson = Person::factory()->create(['user_id' => $leadUser->id]);

        $watcherUser = User::factory()->create();
        $watcherPerson = Person::factory()->create(['user_id' => $watcherUser->id]);

        $assignment = Assignment::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'in_progress',
            'responsible_person_id' => $leadPerson->id,
        ]);
        $assignment->watchers()->attach($watcherPerson->id, ['role' => 'watcher']);

        Livewire::actingAs($leadUser->fresh())
            ->test(AssignmentDetail::class, ['assignment' => $assignment->fresh()])
            ->call('openProgressForm')
            ->set('progress_percent_complete', 20)
            ->call('recordProgress')
            ->assertHasNoErrors();

        Notification::assertSentTo($leadUser, AssignmentStatusChanged::class);
        Notification::assertSentTo($watcherUser, AssignmentStatusChanged::class);
    }
}
