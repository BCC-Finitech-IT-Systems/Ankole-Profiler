<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyApprovalPublicationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Policy $policy;
    private PolicyVersion $draft;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
        $this->draft = PolicyVersion::factory()->create([
            'policy_id' => $this->policy->id, 'version_number' => 1, 'status' => 'draft',
        ]);
        $this->policy->update(['current_version_id' => $this->draft->id]);
    }

    private function dioceseAdmin(array $permissions)
    {
        return $this->affiliatedUser('Organization Admin', $this->diocese, null, $permissions);
    }

    public function test_publish_is_blocked_without_synod_date_reference_or_document()
    {
        $admin = $this->dioceseAdmin(['view-policies', 'approve-policies', 'publish-policies']);
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        // Missing everything: not approved yet.
        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->set('selectedInstitutionIds', [(string) $institution->id])
            ->call('publish')
            ->assertForbidden();

        // Approve without synod info first isn't possible (approve() requires hasSynodApproval),
        // so directly verify the version-level gates.
        $this->assertFalse($this->draft->isPublishable());

        $this->draft->update(['synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1']);
        $this->assertFalse($this->draft->fresh()->isPublishable(), 'Still missing document and approved status');

        $this->draft->update(['document_path' => 'x.pdf', 'status' => 'approved']);
        $this->assertTrue($this->draft->fresh()->isPublishable());
    }

    public function test_publish_succeeds_once_synod_metadata_and_document_are_present()
    {
        $this->draft->update([
            'synod_approval_date' => now(),
            'synod_approval_reference' => 'REF-1',
            'document_path' => 'x.pdf',
            'status' => 'approved',
        ]);

        $institutionA = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $institutionB = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        $admin = $this->dioceseAdmin(['view-policies', 'publish-policies']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->set('selectedInstitutionIds', [(string) $institutionA->id, (string) $institutionB->id])
            ->set('publishDueDate', now()->addDays(30)->toDateString())
            ->call('publish')
            ->assertHasNoErrors();

        $this->assertEquals(2, PolicyPublication::where('policy_version_id', $this->draft->id)->count());
        $this->assertEquals('sent', PolicyPublication::where('organization_id', $institutionA->id)->first()->status);
    }

    public function test_department_manager_cannot_approve_or_publish()
    {
        $department = Department::factory()->create(['organization_id' => $this->diocese->id]);
        // Owning department set so the manager can VIEW (mount() succeeds);
        // the assertion below is specifically about approve-policies scope.
        $this->policy->update(['department_id' => $department->id]);

        $manager = $this->affiliatedUser('Department Manager', $this->diocese, $department, [
            'view-policies', 'create-policies', 'edit-policies',
        ]);

        $this->draft->update([
            'synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1', 'document_path' => 'x.pdf',
        ]);

        Livewire::actingAs($manager)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->call('approve')
            ->assertForbidden();
    }

    public function test_publish_to_all_is_scoped_to_the_diocese_subtree()
    {
        $this->draft->update([
            'synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1',
            'document_path' => 'x.pdf', 'status' => 'approved',
        ]);

        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $foreignInstitution = Organization::factory()->institution()->create(); // not under this diocese

        $admin = $this->dioceseAdmin(['view-policies', 'publish-policies']);

        $component = Livewire::actingAs($admin)->test(PolicyDetail::class, ['policy' => $this->policy]);
        $component->call('selectAllInstitutions');

        $selected = $component->get('selectedInstitutionIds');
        $this->assertContains((string) $institution->id, $selected);
        $this->assertNotContains((string) $foreignInstitution->id, $selected);
    }

    public function test_republishing_the_same_institution_is_idempotent()
    {
        $this->draft->update([
            'synod_approval_date' => now(), 'synod_approval_reference' => 'REF-1',
            'document_path' => 'x.pdf', 'status' => 'approved',
        ]);
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        PolicyPublication::create([
            'policy_version_id' => $this->draft->id,
            'policy_id' => $this->policy->id,
            'organization_id' => $institution->id,
            'status' => 'not_sent',
        ]);

        $admin = $this->dioceseAdmin(['view-policies', 'publish-policies']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->set('selectedInstitutionIds', [(string) $institution->id])
            ->call('publish')
            ->assertHasNoErrors();

        $this->assertEquals(1, PolicyPublication::where('policy_version_id', $this->draft->id)->where('organization_id', $institution->id)->count());
    }
}
