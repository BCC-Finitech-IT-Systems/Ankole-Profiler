<?php

namespace Tests\Feature\Policies;

use App\Livewire\Policies\PolicyAdoptionDashboard;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyPublication;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class PolicyDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $diocese;
    private Policy $policy;
    private PolicyVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diocese = Organization::factory()->diocese()->create();
        $this->policy = Policy::factory()->create(['organization_id' => $this->diocese->id]);
        $this->version = PolicyVersion::factory()->published()->create(['policy_id' => $this->policy->id, 'version_number' => 1]);
    }

    private function makePublication(string $status, ?string $dueDate = null, string $exceptionStatus = 'none'): PolicyPublication
    {
        $institution = Organization::factory()->institution()->create(['parent_organization_id' => $this->diocese->id]);

        return PolicyPublication::create([
            'policy_version_id' => $this->version->id,
            'policy_id' => $this->policy->id,
            'organization_id' => $institution->id,
            'status' => $status,
            'due_date' => $dueDate,
            'exception_status' => $exceptionStatus,
        ]);
    }

    private function dioceseAdmin()
    {
        return $this->affiliatedUser('Organization Admin', $this->diocese, null, [
            'view-policy-dashboard', 'decide-policy-exceptions',
        ]);
    }

    public function test_coverage_percentage_reflects_adopted_and_partially_adopted_share()
    {
        $this->makePublication('adopted');
        $this->makePublication('partially_adopted');
        $this->makePublication('sent');
        $this->makePublication('acknowledged');

        Livewire::actingAs($this->dioceseAdmin())
            ->test(PolicyAdoptionDashboard::class)
            ->assertViewHas('coveragePercent', 50);
    }

    public function test_overdue_scope_excludes_adopted_and_approved_exceptions()
    {
        $this->makePublication('sent', now()->subDays(5)->toDateString());
        $this->makePublication('adopted', now()->subDays(5)->toDateString());
        $this->makePublication('sent', now()->subDays(5)->toDateString(), 'approved');
        $this->makePublication('sent', now()->addDays(5)->toDateString());

        Livewire::actingAs($this->dioceseAdmin())
            ->test(PolicyAdoptionDashboard::class)
            ->assertViewHas('overdueCount', 1);
    }

    public function test_exception_queue_lists_only_requested_exceptions()
    {
        $this->makePublication('exception_requested', null, 'requested');
        $this->makePublication('sent', null, 'approved');
        $this->makePublication('sent', null, 'none');

        $component = Livewire::actingAs($this->dioceseAdmin())->test(PolicyAdoptionDashboard::class);

        $this->assertCount(1, $component->viewData('exceptions'));
    }
}
