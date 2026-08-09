<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyDetail;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyVersioningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Policy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
    }

    private function dioceseAdmin(array $permissions)
    {
        return $this->affiliatedUser('Organization Admin', $this->diocese, null, $permissions);
    }

    public function test_create_revision_produces_a_new_draft_with_supersedes_pointer()
    {
        $v1 = PolicyVersion::factory()->published()->create([
            'policy_id' => $this->policy->id, 'version_number' => 1, 'version_label' => '1.0',
        ]);
        $this->policy->update(['current_version_id' => $v1->id, 'status' => 'active']);

        $admin = $this->dioceseAdmin(['view-policies', 'create-policies']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->call('createRevision')
            ->assertHasNoErrors();

        $v2 = PolicyVersion::where('policy_id', $this->policy->id)->where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $this->assertEquals('draft', $v2->status);
        $this->assertEquals($v1->id, $v2->supersedes_version_id);
    }

    public function test_editing_an_approved_version_throws_and_leaves_the_row_unchanged()
    {
        $version = PolicyVersion::factory()->approved()->create([
            'policy_id' => $this->policy->id, 'version_number' => 1, 'summary' => 'original',
        ]);

        $this->expectException(\DomainException::class);

        $version->summary = 'changed after approval';
        $version->save();

        $this->assertEquals('original', $version->fresh()->summary);
    }

    public function test_publishing_a_new_version_supersedes_the_previous_published_version_without_altering_it()
    {
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        $v1 = PolicyVersion::factory()->published()->create([
            'policy_id' => $this->policy->id, 'version_number' => 1, 'version_label' => '1.0',
        ]);
        $originalDocumentHash = $v1->document_hash;
        $this->policy->update(['current_version_id' => $v1->id, 'status' => 'active']);

        $v2 = PolicyVersion::factory()->approved()->create([
            'policy_id' => $this->policy->id, 'version_number' => 2, 'version_label' => '2.0',
        ]);

        $admin = $this->dioceseAdmin(['view-policies', 'publish-policies']);

        Livewire::actingAs($admin)
            ->test(PolicyDetail::class, ['policy' => $this->policy])
            ->set('selectedInstitutionIds', [(string) $institution->id])
            ->set('publishDueDate', now()->addDays(30)->toDateString())
            ->call('publish')
            ->assertHasNoErrors();

        $v1->refresh();
        $v2->refresh();

        $this->assertEquals('superseded', $v1->status);
        $this->assertEquals($originalDocumentHash, $v1->document_hash, 'Superseding must not mutate the prior version row');
        $this->assertEquals('published', $v2->status);
        $this->assertEquals($v2->id, $this->policy->fresh()->current_version_id);
    }

    public function test_version_number_is_unique_per_policy()
    {
        PolicyVersion::factory()->create(['policy_id' => $this->policy->id, 'version_number' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PolicyVersion::factory()->create(['policy_id' => $this->policy->id, 'version_number' => 1]);
    }

    public function test_approved_version_cannot_be_deleted()
    {
        $version = PolicyVersion::factory()->approved()->create(['policy_id' => $this->policy->id, 'version_number' => 1]);

        $this->expectException(\DomainException::class);

        $version->delete();
    }
}
