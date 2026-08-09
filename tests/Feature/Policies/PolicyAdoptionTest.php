<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyAdoptionDashboard;
use App\Livewire\Policies\PolicyAdoptionTracker;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyAdoptionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Organization $institutionA;
    private Organization $institutionB;
    private PolicyPublication $publicationA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->institutionA = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $this->institutionB = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        $policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
        $version = PolicyVersion::factory()->published()->create(['policy_id' => $policy->id, 'version_number' => 1]);

        $this->publicationA = PolicyPublication::create([
            'policy_version_id' => $version->id,
            'policy_id' => $policy->id,
            'organization_id' => $this->institutionA->id,
            'status' => 'sent',
            'due_date' => now()->addDays(30)->toDateString(),
            'sent_at' => now(),
        ]);
    }

    private function institutionAdmin(Organization $institution)
    {
        return $this->affiliatedUser('Organization Admin', $institution, null, [
            'view-policies', 'view-policy-adoption', 'update-policy-adoption',
        ]);
    }

    public function test_institution_admin_can_acknowledge_and_record_adoption_with_evidence()
    {
        $admin = $this->institutionAdmin($this->institutionA);

        Livewire::actingAs($admin)
            ->test(PolicyAdoptionTracker::class)
            ->call('acknowledge', $this->publicationA->id)
            ->assertHasNoErrors();

        $this->assertEquals('acknowledged', $this->publicationA->fresh()->status);

        Livewire::actingAs($admin)
            ->test(PolicyAdoptionTracker::class)
            ->call('openAdoptionForm', $this->publicationA->id)
            ->set('adoption_date', now()->toDateString())
            ->set('implementation_notes', 'Rolled out to all staff')
            ->set('adoptionStatus', 'adopted')
            ->call('recordAdoption')
            ->assertHasNoErrors();

        $this->publicationA->refresh();
        $this->assertEquals('adopted', $this->publicationA->status);
        $this->assertEquals('Rolled out to all staff', $this->publicationA->implementation_notes);
    }

    public function test_partial_adoption_is_recorded()
    {
        $admin = $this->institutionAdmin($this->institutionA);

        Livewire::actingAs($admin)
            ->test(PolicyAdoptionTracker::class)
            ->call('openAdoptionForm', $this->publicationA->id)
            ->set('adoption_date', now()->toDateString())
            ->set('adoptionStatus', 'partially_adopted')
            ->call('recordAdoption')
            ->assertHasNoErrors();

        $this->assertEquals('partially_adopted', $this->publicationA->fresh()->status);
    }

    public function test_institution_can_request_an_exception()
    {
        $admin = $this->institutionAdmin($this->institutionA);

        Livewire::actingAs($admin)
            ->test(PolicyAdoptionTracker::class)
            ->call('openExceptionModal', $this->publicationA->id)
            ->set('exception_reason', 'Awaiting board approval')
            ->call('requestException')
            ->assertHasNoErrors();

        $this->publicationA->refresh();
        $this->assertEquals('requested', $this->publicationA->exception_status);
        $this->assertEquals('exception_requested', $this->publicationA->status);
    }

    public function test_institution_a_cannot_update_institution_bs_publication()
    {
        $publicationB = PolicyPublication::create([
            'policy_version_id' => $this->publicationA->policy_version_id,
            'policy_id' => $this->publicationA->policy_id,
            'organization_id' => $this->institutionB->id,
            'status' => 'sent',
        ]);

        $adminA = $this->institutionAdmin($this->institutionA);

        Livewire::actingAs($adminA)
            ->test(PolicyAdoptionTracker::class)
            ->call('acknowledge', $publicationB->id)
            ->assertForbidden();
    }

    public function test_diocese_admin_decides_exceptions_but_institution_cannot_decide_its_own()
    {
        $this->publicationA->update(['exception_status' => 'requested', 'status' => 'exception_requested']);

        // Grant view-policy-dashboard too, so the denial below is specifically
        // the decideException diocese-scope check, not the render() gate.
        $institutionAdmin = $this->affiliatedUser('Organization Admin', $this->institutionA, null, [
            'view-policies', 'view-policy-adoption', 'update-policy-adoption', 'view-policy-dashboard',
        ]);
        Livewire::actingAs($institutionAdmin)
            ->test(PolicyAdoptionDashboard::class)
            ->call('approveException', $this->publicationA->id)
            ->assertForbidden();

        $dioceseAdmin = $this->affiliatedUser('Organization Admin', $this->diocese, null, [
            'view-policy-dashboard', 'decide-policy-exceptions',
        ]);

        Livewire::actingAs($dioceseAdmin)
            ->test(PolicyAdoptionDashboard::class)
            ->call('approveException', $this->publicationA->id)
            ->assertHasNoErrors();

        $this->assertEquals('approved', $this->publicationA->fresh()->exception_status);
    }
}
