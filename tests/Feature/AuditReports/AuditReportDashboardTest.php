<?php

namespace Tests\Feature\AuditReports;

use App\Livewire\AuditReports\AuditReportDashboard;
use App\Models\AuditReport;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AuditReportDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    public function test_bucket_counts_reflect_only_in_scope_reports()
    {
        AuditReport::factory()->create(['organization_id' => $this->organization->id, 'status' => 'draft']);
        AuditReport::factory()->create(['organization_id' => $this->organization->id, 'status' => 'issued']);
        AuditReport::factory()->create(['organization_id' => $this->organization->id, 'status' => 'closed']);

        AuditReport::factory()->create(['organization_id' => Organization::factory()->create()->id, 'status' => 'issued']);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-audit-dashboard']);

        Livewire::actingAs($admin)
            ->test(AuditReportDashboard::class)
            ->assertViewHas('total', 3)
            ->assertViewHas('draft', 1)
            ->assertViewHas('issued', 1)
            ->assertViewHas('closed', 1);
    }

    public function test_status_filter_narrows_the_scoped_query()
    {
        AuditReport::factory()->create(['organization_id' => $this->organization->id, 'status' => 'draft']);
        AuditReport::factory()->create(['organization_id' => $this->organization->id, 'status' => 'issued']);

        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-audit-dashboard']);

        Livewire::actingAs($admin)
            ->test(AuditReportDashboard::class)
            ->set('statusFilter', 'issued')
            ->assertViewHas('total', 1);
    }
}
