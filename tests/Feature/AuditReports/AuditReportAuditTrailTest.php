<?php

namespace Tests\Feature\AuditReports;

use App\Livewire\AuditReports\AuditReportAuditTrail;
use App\Livewire\AuditReports\AuditReportsManagement;
use App\Models\AuditLog;
use App\Models\AuditReport;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AuditReportAuditTrailTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    public function test_create_action_writes_exactly_one_audit_row()
    {
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-audit-reports', 'create-audit-reports']);

        Livewire::actingAs($admin)
            ->test(AuditReportsManagement::class)
            ->call('create')
            ->set('organization_id', $this->organization->id)
            ->set('title', 'Compliance Review')
            ->set('audit_type', 'compliance')
            ->set('issuing_body', 'Internal Audit Unit')
            ->set('issue_date', now()->toDateString())
            ->call('save');

        $report = AuditReport::where('title', 'Compliance Review')->first();

        $this->assertEquals(1, AuditLog::where('event', 'audit_report.created')
            ->where('auditable_type', AuditReport::class)
            ->where('auditable_id', $report->id)
            ->count());
    }

    public function test_audit_log_rows_are_immutable()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id]);

        $log = AuditLog::create([
            'auditable_type' => AuditReport::class,
            'auditable_id' => $report->id,
            'event' => 'audit_report.created',
            'created_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $log->update(['event' => 'tampered']);
    }

    public function test_audit_log_rows_cannot_be_deleted()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id]);

        $log = AuditLog::create([
            'auditable_type' => AuditReport::class,
            'auditable_id' => $report->id,
            'event' => 'audit_report.created',
            'created_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $log->delete();
    }

    public function test_user_without_view_audit_logs_permission_is_forbidden()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id]);
        $viewer = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-audit-reports']);

        Livewire::actingAs($viewer)
            ->test(AuditReportAuditTrail::class, ['report' => $report])
            ->assertForbidden();
    }

    public function test_audit_trail_is_isolated_between_organizations()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id]);
        $otherOrg = Organization::factory()->create();
        $otherAdmin = $this->affiliatedUser('Organization Admin', $otherOrg, null, ['view-audit-reports', 'view-audit-logs']);

        Livewire::actingAs($otherAdmin)
            ->test(AuditReportAuditTrail::class, ['report' => $report])
            ->assertForbidden();
    }
}
