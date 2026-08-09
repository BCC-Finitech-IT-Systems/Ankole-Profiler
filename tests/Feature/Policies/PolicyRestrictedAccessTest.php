<?php

namespace Tests\Feature\Policies;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use App\Models\PolicyVersionAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyRestrictedAccessTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Organization $publishedToInstitution;
    private Organization $unrelatedInstitution;
    private Policy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->diocese = Organization::factory()->diocese()->create();
        $this->publishedToInstitution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $this->unrelatedInstitution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);
        $this->policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
    }

    private function makeVersion(string $visibility): PolicyVersion
    {
        $file = UploadedFile::fake()->create('policy.pdf', 100, 'application/pdf');
        $path = $file->store('policies/test', 'local');

        $version = PolicyVersion::factory()->published()->create([
            'policy_id' => $this->policy->id,
            'version_number' => 1,
            'visibility' => $visibility,
            'document_path' => $path,
            'document_original_name' => 'policy.pdf',
        ]);

        PolicyPublication::create([
            'policy_version_id' => $version->id,
            'policy_id' => $this->policy->id,
            'organization_id' => $this->publishedToInstitution->id,
            'status' => 'sent',
        ]);

        return $version;
    }

    private function institutionUser(Organization $institution)
    {
        return $this->affiliatedUser('Organization Admin', $institution, null, [
            'view-policies', 'download-policy-documents',
        ]);
    }

    public function test_diocese_wide_version_is_downloadable_by_a_published_to_institution()
    {
        $version = $this->makeVersion('diocese_wide');
        $user = $this->institutionUser($this->publishedToInstitution);

        $response = $this->actingAs($user)->get(route('policies.documents.version', $version));

        $response->assertOk();
    }

    public function test_restricted_version_denies_a_user_outside_the_audience()
    {
        $version = $this->makeVersion('restricted');
        PolicyVersionAudience::create(['policy_version_id' => $version->id, 'role_name' => 'Some Other Role']);

        $user = $this->institutionUser($this->unrelatedInstitution);

        $response = $this->actingAs($user)->get(route('policies.documents.version', $version));

        $response->assertForbidden();
        $this->assertEquals(0, AuditLog::where('event', 'document.downloaded')->count());
    }

    public function test_restricted_version_allows_a_published_to_institution_with_no_audience_rows()
    {
        $version = $this->makeVersion('restricted');
        $user = $this->institutionUser($this->publishedToInstitution);

        $response = $this->actingAs($user)->get(route('policies.documents.version', $version));

        $response->assertOk();
        $this->assertEquals(1, AuditLog::where('event', 'document.downloaded')->count());
    }

    public function test_restricted_version_allows_a_matching_organization_audience_row()
    {
        $version = $this->makeVersion('restricted');
        PolicyVersionAudience::create(['policy_version_id' => $version->id, 'organization_id' => $this->unrelatedInstitution->id]);

        // unrelatedInstitution has no publication row, but matches the audience rule.
        PolicyPublication::create([
            'policy_version_id' => $version->id,
            'policy_id' => $this->policy->id,
            'organization_id' => $this->unrelatedInstitution->id,
            'status' => 'sent',
        ]);

        $user = $this->institutionUser($this->unrelatedInstitution);

        $response = $this->actingAs($user)->get(route('policies.documents.version', $version));

        $response->assertOk();
    }
}
