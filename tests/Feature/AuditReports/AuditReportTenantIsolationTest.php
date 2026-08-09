<?php

namespace Tests\Feature\AuditReports;

use App\Livewire\AuditReports\AuditReportDashboard;
use App\Livewire\AuditReports\AuditReportDetail;
use App\Livewire\AuditReports\AuditReportsManagement;
use App\Models\AuditReport;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AuditReportTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $orgA;
    private Organization $orgB;
    private AuditReport $reportA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();
        $this->reportA = AuditReport::factory()->create(['organization_id' => $this->orgA->id, 'title' => 'Org A Audit']);
    }

    public function test_list_excludes_other_organizations()
    {
        AuditReport::factory()->create(['organization_id' => $this->orgB->id, 'title' => 'Org B Audit']);

        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-audit-reports']);

        Livewire::actingAs($adminB)
            ->test(AuditReportsManagement::class)
            ->assertDontSee('Org A Audit')
            ->assertSee('Org B Audit');
    }

    public function test_direct_show_of_a_foreign_report_is_forbidden()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-audit-reports']);

        Livewire::actingAs($adminB)
            ->test(AuditReportDetail::class, ['report' => $this->reportA])
            ->assertForbidden();
    }

    public function test_dashboard_excludes_other_organizations()
    {
        $adminB = $this->affiliatedUser('Organization Admin', $this->orgB, null, ['view-audit-dashboard']);

        Livewire::actingAs($adminB)
            ->test(AuditReportDashboard::class)
            ->assertViewHas('total', 0);
    }
}
