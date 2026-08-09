<?php

namespace Tests\Feature\AuditReports;

use App\Models\AuditDocument;
use App\Models\AuditLog;
use App\Models\AuditReport;
use App\Models\AuditReportAudience;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsAffiliatedUsers;
use Tests\TestCase;

class AuditReportRestrictedAccessTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAffiliatedUsers;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->organization = Organization::factory()->create();
    }

    private function uploadedDocument(AuditReport $report): AuditDocument
    {
        $file = UploadedFile::fake()->create('management-letter.pdf', 100, 'application/pdf');
        $path = $file->store('audit-reports/test', 'local');

        return AuditDocument::create([
            'audit_report_id' => $report->id,
            'document_type' => 'management_letter',
            'version_number' => 1,
            'is_current' => true,
            'path' => $path,
            'original_name' => 'management-letter.pdf',
        ]);
    }

    private function viewerWithNoScope(array $permissions = ['view-audit-reports', 'download-audit-documents'])
    {
        return $this->affiliatedUser('Organization Admin', Organization::factory()->create(), null, $permissions);
    }

    public function test_manager_scope_can_download_regardless_of_restriction()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => true]);
        $document = $this->uploadedDocument($report);
        $admin = $this->affiliatedUser('Organization Admin', $this->organization, null, ['view-audit-reports', 'download-audit-documents']);

        $response = $this->actingAs($admin)->get(route('audit-reports.documents.download', $document));

        $response->assertOk();
    }

    public function test_restricted_report_denies_an_outsider_with_no_audience()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => true]);
        $document = $this->uploadedDocument($report);

        $outsider = $this->viewerWithNoScope();

        $response = $this->actingAs($outsider)->get(route('audit-reports.documents.download', $document));

        $response->assertForbidden();
        $this->assertEquals(0, AuditLog::where('event', 'audit_document.downloaded')->count());
    }

    public function test_restricted_report_allows_a_matching_role_audience()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => true]);
        $document = $this->uploadedDocument($report);
        AuditReportAudience::create(['audit_report_id' => $report->id, 'role_name' => 'Organization Admin']);

        $matchingUser = $this->viewerWithNoScope();

        $response = $this->actingAs($matchingUser)->get(route('audit-reports.documents.download', $document));

        $response->assertOk();
        $this->assertEquals(1, AuditLog::where('event', 'audit_document.downloaded')->count());
    }

    public function test_restricted_report_allows_a_matching_department_audience()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => true]);
        $document = $this->uploadedDocument($report);

        $otherOrg = Organization::factory()->create();
        $department = Department::factory()->create(['organization_id' => $otherOrg->id]);
        AuditReportAudience::create(['audit_report_id' => $report->id, 'department_id' => $department->id]);

        $deptManager = $this->affiliatedUser('Department Manager', $otherOrg, $department, ['view-audit-reports', 'download-audit-documents']);

        $response = $this->actingAs($deptManager)->get(route('audit-reports.documents.download', $document));

        $response->assertOk();
    }

    public function test_restricted_report_allows_a_matching_person_audience()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => true]);
        $document = $this->uploadedDocument($report);

        $namedUser = $this->viewerWithNoScope();
        $person = Person::where('user_id', $namedUser->id)->firstOrFail();

        AuditReportAudience::create(['audit_report_id' => $report->id, 'person_id' => $person->id]);

        $response = $this->actingAs($namedUser)->get(route('audit-reports.documents.download', $document));

        $response->assertOk();
        $this->assertEquals(1, AuditLog::where('event', 'audit_document.downloaded')->count());
    }

    public function test_non_restricted_report_still_requires_manager_scope()
    {
        $report = AuditReport::factory()->create(['organization_id' => $this->organization->id, 'restricted' => false]);
        $document = $this->uploadedDocument($report);
        $outsider = $this->viewerWithNoScope();

        $response = $this->actingAs($outsider)->get(route('audit-reports.documents.download', $document));

        $response->assertForbidden();
    }
}
