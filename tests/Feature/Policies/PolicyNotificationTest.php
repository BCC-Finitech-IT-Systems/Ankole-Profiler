<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyDetail;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyVersion;
use App\Notifications\PolicyIssued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyNotificationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    public function test_publishing_notifies_only_recipients_at_the_published_to_institution()
    {
        Notification::fake();

        $diocese = Organization::factory()->diocese()->create();
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $diocese->id]);
        $unrelatedInstitution = Organization::factory()->institution()->create(['parent_organization_id' => $diocese->id]);

        $recipient = $this->affiliatedUser('Organization Admin', $institution, null, ['view-policy-adoption']);
        $unrelatedRecipient = $this->affiliatedUser('Organization Admin', $unrelatedInstitution, null, ['view-policy-adoption']);

        $policy = Policy::factory()->create(['organization_id' => $diocese->id]);
        $draft = PolicyVersion::factory()->create([
            'policy_id' => $policy->id, 'version_number' => 1, 'status' => 'approved',
            'synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1', 'document_path' => 'x.pdf',
        ]);
        $policy->update(['current_version_id' => $draft->id]);

        $admin = $this->affiliatedUser('Organization Admin', $diocese, null, ['view-policies', 'publish-policies']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $policy])
            ->set('selectedInstitutionIds', [(string) $institution->id])
            ->set('publishDueDate', now()->addDays(30)->toDateString())
            ->call('publish')
            ->assertHasNoErrors();

        Notification::assertSentTo($recipient, PolicyIssued::class);
        Notification::assertNotSentTo($unrelatedRecipient, PolicyIssued::class);
    }

    /**
     * publish() validates targetIds and aborts with 422 before opening the
     * DB::transaction if none of the submitted ids are valid institutions —
     * the same code path (abort before the transaction) that protects
     * against notifying on any failure that happens before commit.
     */
    public function test_no_notification_is_sent_when_publish_is_rejected_before_the_transaction()
    {
        $diocese = Organization::factory()->diocese()->create();
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $diocese->id]);
        $this->affiliatedUser('Organization Admin', $institution, null, ['view-policy-adoption']);

        $policy = Policy::factory()->create(['organization_id' => $diocese->id]);
        $draft = PolicyVersion::factory()->create([
            'policy_id' => $policy->id, 'version_number' => 1, 'status' => 'approved',
            'synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1', 'document_path' => 'x.pdf',
        ]);
        $policy->update(['current_version_id' => $draft->id]);

        $admin = $this->affiliatedUser('Organization Admin', $diocese, null, ['view-policies', 'publish-policies']);

        Notification::fake();

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $policy])
            ->set('selectedInstitutionIds', ['999999']) // not a real institution under this diocese
            ->set('publishDueDate', now()->addDays(30)->toDateString())
            ->call('publish')
            ->assertStatus(422);

        Notification::assertNothingSent();
        $this->assertEquals('approved', $draft->fresh()->status, 'Version must remain unpublished');
    }
}
