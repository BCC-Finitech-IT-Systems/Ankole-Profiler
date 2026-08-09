<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PoliciesManagement;
use App\Livewire\Policies\PolicyAdoptionDashboard;
use App\Livewire\Policies\PolicyDetail;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $dioceseA;
    private Organization $dioceseB;
    private Policy $policyA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dioceseA = Organization::factory()->diocese()->create();
        $this->dioceseB = Organization::factory()->diocese()->create();
        $this->policyA = Policy::factory()->create(['organization_id' => $this->dioceseA->id, 'title' => 'Diocese A Policy']);
    }

    private function adminOf(Organization $diocese, array $permissions)
    {
        return $this->affiliatedUser('Organization Admin', $diocese, null, $permissions);
    }

    public function test_list_excludes_other_dioceses_policies()
    {
        Policy::factory()->create(['organization_id' => $this->dioceseB->id, 'title' => 'Diocese B Policy']);

        $adminB = $this->adminOf($this->dioceseB, ['view-policies']);

        Livewire::actingAs($adminB)
            ->test(PoliciesManagement::class)
            ->assertDontSee('Diocese A Policy')
            ->assertSee('Diocese B Policy');
    }

    public function test_direct_show_of_a_foreign_policy_is_forbidden()
    {
        $adminB = $this->adminOf($this->dioceseB, ['view-policies']);

        Livewire::actingAs($adminB)
            ->test(PolicyDetail::class, ['policy' => $this->policyA])
            ->assertForbidden();
    }

    public function test_foreign_publication_is_forbidden_for_adoption_update()
    {
        $institutionA = Organization::factory()->institution()->create(['parent_organization_id' => $this->dioceseA->id]);
        $version = PolicyVersion::factory()->published()->create(['policy_id' => $this->policyA->id, 'version_number' => 1]);
        $publication = PolicyPublication::create([
            'policy_version_id' => $version->id, 'policy_id' => $this->policyA->id,
            'organization_id' => $institutionA->id, 'status' => 'sent',
        ]);

        $adminB = $this->adminOf($this->dioceseB, ['view-policy-adoption', 'update-policy-adoption']);

        Livewire::actingAs($adminB)
            ->test(\App\Livewire\Policies\PolicyAdoptionTracker::class)
            ->call('acknowledge', $publication->id)
            ->assertForbidden();
    }

    public function test_dashboard_counts_exclude_other_dioceses()
    {
        $institutionA = Organization::factory()->institution()->create(['parent_organization_id' => $this->dioceseA->id]);
        $version = PolicyVersion::factory()->published()->create(['policy_id' => $this->policyA->id, 'version_number' => 1]);
        PolicyPublication::create([
            'policy_version_id' => $version->id, 'policy_id' => $this->policyA->id,
            'organization_id' => $institutionA->id, 'status' => 'adopted',
        ]);

        $adminB = $this->adminOf($this->dioceseB, ['view-policy-dashboard']);

        Livewire::actingAs($adminB)
            ->test(PolicyAdoptionDashboard::class)
            ->assertViewHas('coveragePercent', 0);
    }
}
