<?php

namespace Tests\Feature\AuditReports;

use App\Livewire\AuditReports\AuditReportDetail;
use App\Livewire\AuditReports\AuditReportsManagement;
use App\Models\AuditReport;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AuditReportCrudTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->department = Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function admin(array $permissions = ['view-audit-reports', 'create-audit-reports', 'edit-audit-reports', 'archive-audit-reports'])
    {
        return $this->affiliatedUser('Organization Admin', $this->organization, null, $permissions);
    }

    public function test_admin_can_record_an_audit_report()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(AuditReportsManagement::class)
            ->call('create')
            ->set('organization_id', $this->organization->id)
            ->set('department_id', $this->department->id)
            ->set('title', 'FY2026 Financial Audit')
            ->set('audit_type', 'financial')
            ->set('issuing_body', 'External Auditors Ltd')
            ->set('issue_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $report = AuditReport::where('title', 'FY2026 Financial Audit')->first();
        $this->assertNotNull($report);
        $this->assertEquals('draft', $report->status);
    }

    public function test_required_fields_are_validated()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(AuditReportsManagement::class)
            ->call('create')
            ->set('organization_id', $this->organization->id)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title', 'issuing_body', 'issue_date']);
    }

    public function test_add_button_hidden_without_create_permission()
    {
        $viewer = $this->admin(['view-audit-reports']);

        $response = $this->actingAs($viewer)->get(route('audit-reports.index'));

        $response->assertOk();
        $response->assertDontSee('Add Audit Report');
    }

    public function test_add_button_is_rendered_inside_the_livewire_tracked_root()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('audit-reports.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['wire:id', 'Add Audit Report'], false);
    }

    public function test_admin_can_archive_a_report()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id]);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(AuditReportsManagement::class)
            ->call('confirmArchive', $report->id)
            ->call('archive');

        $this->assertSoftDeleted('audit_reports', ['id' => $report->id]);
    }

    public function test_outsider_department_cannot_edit()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);

        $outsider = $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, ['view-audit-reports', 'edit-audit-reports']);

        Livewire::actingAs($outsider)
            ->test(AuditReportDetail::class, ['report' => $report])
            ->assertForbidden();
    }
}
