<?php

namespace App\Livewire\AuditReports;

use App\Models\AuditDocument;
use App\Models\AuditReport;
use App\Models\AuditReportAudience;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class AuditReportDetail extends Component
{
    use WithFileUploads;

    public AuditReport $report;

    public string $status = '';

    // Follow-up owner assignment
    public string $followUpOwnerSearch = '';
    public array $followUpOwnerResults = [];
    public ?int $responsible_follow_up_owner_id = null;

    // Document upload
    public string $document_type = 'report';
    public $document;

    // Audience management
    public string $audienceType = 'organization';
    public ?int $audienceOrganizationId = null;
    public ?int $audienceDepartmentId = null;
    public string $audienceRoleName = '';
    public string $audiencePersonSearch = '';
    public array $audiencePersonResults = [];
    public ?int $audiencePersonId = null;

    public function mount(AuditReport $report): void
    {
        $this->authorize('view', $report);
        $this->report = $report;
        $this->status = $report->status;
        $this->responsible_follow_up_owner_id = $report->responsible_follow_up_owner_id;
        $this->followUpOwnerSearch = $report->followUpOwner
            ? trim($report->followUpOwner->given_name . ' ' . $report->followUpOwner->family_name)
            : '';
    }

    // ─── Status transition ───────────────────────────────────────────────────

    public function updateStatus(): void
    {
        $this->authorize('update', $this->report);

        $this->validate([
            'status' => 'required|in:draft,issued,under_review,closed',
        ]);

        $this->report->update(['status' => $this->status, 'updated_by' => Auth::id()]);

        AuditLogger::record($this->report, 'audit_report.status_changed', ['status' => $this->status], $this->report->organization_id);
        session()->flash('success', 'Status updated.');
    }

    // ─── Follow-up owner ──────────────────────────────────────────────────────

    public function updatedFollowUpOwnerSearch(): void
    {
        if (strlen($this->followUpOwnerSearch) < 2) {
            $this->followUpOwnerResults = [];
            return;
        }

        $this->followUpOwnerResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->followUpOwnerSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->followUpOwnerSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->toArray();
    }

    public function selectFollowUpOwner(int $personId, string $name): void
    {
        $this->responsible_follow_up_owner_id = $personId;
        $this->followUpOwnerSearch = $name;
        $this->followUpOwnerResults = [];
    }

    public function saveFollowUpOwner(): void
    {
        $this->authorize('update', $this->report);

        $this->validate([
            'responsible_follow_up_owner_id' => 'nullable|exists:persons,id',
        ]);

        $this->report->update([
            'responsible_follow_up_owner_id' => $this->responsible_follow_up_owner_id,
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::record($this->report, 'audit_report.follow_up_owner_assigned', [], $this->report->organization_id);
        session()->flash('success', 'Follow-up owner assigned.');
    }

    // ─── Documents ────────────────────────────────────────────────────────────

    public function uploadDocument(): void
    {
        $this->authorize('update', $this->report);

        $this->validate([
            'document_type' => 'required|in:report,management_letter,management_response,evidence,other',
            'document' => 'required|file|max:20480',
        ]);

        $nextVersion = AuditDocument::where('audit_report_id', $this->report->id)
            ->where('document_type', $this->document_type)
            ->max('version_number') + 1;

        $path = $this->document->store('audit-reports/' . $this->report->id . '/' . $this->document_type, 'local');

        DB::transaction(function () use ($nextVersion, $path) {
            AuditDocument::where('audit_report_id', $this->report->id)
                ->where('document_type', $this->document_type)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            AuditDocument::create([
                'audit_report_id' => $this->report->id,
                'document_type' => $this->document_type,
                'version_number' => $nextVersion,
                'is_current' => true,
                'path' => $path,
                'original_name' => $this->document->getClientOriginalName(),
                'mime' => $this->document->getMimeType(),
                'size' => $this->document->getSize(),
                'hash' => hash_file('sha256', $this->document->getRealPath()),
                'uploaded_by' => Auth::id(),
            ]);
        });

        AuditLogger::record($this->report, 'audit_document.uploaded', [
            'document_type' => $this->document_type, 'version_number' => $nextVersion,
        ], $this->report->organization_id);

        $this->document = null;
        session()->flash('success', 'Document uploaded.');
    }

    // ─── Restricted audience ────────────────────────────────────────────────

    public function updatedAudiencePersonSearch(): void
    {
        if (strlen($this->audiencePersonSearch) < 2) {
            $this->audiencePersonResults = [];
            return;
        }

        $this->audiencePersonResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->audiencePersonSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->audiencePersonSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->toArray();
    }

    public function selectAudiencePerson(int $personId, string $name): void
    {
        $this->audiencePersonId = $personId;
        $this->audiencePersonSearch = $name;
        $this->audiencePersonResults = [];
    }

    public function addAudience(): void
    {
        $this->authorize('update', $this->report);

        $this->validate([
            'audienceType' => 'required|in:organization,department,role,person',
            'audienceOrganizationId' => 'required_if:audienceType,organization|nullable|exists:organizations,id',
            'audienceDepartmentId' => 'required_if:audienceType,department|nullable|exists:departments,id',
            'audienceRoleName' => 'required_if:audienceType,role|nullable|string|max:255',
            'audiencePersonId' => 'required_if:audienceType,person|nullable|exists:persons,id',
        ]);

        AuditReportAudience::create([
            'audit_report_id' => $this->report->id,
            'organization_id' => $this->audienceType === 'organization' ? $this->audienceOrganizationId : null,
            'department_id' => $this->audienceType === 'department' ? $this->audienceDepartmentId : null,
            'role_name' => $this->audienceType === 'role' ? $this->audienceRoleName : null,
            'person_id' => $this->audienceType === 'person' ? $this->audiencePersonId : null,
        ]);

        AuditLogger::record($this->report, 'audit_report.visibility_changed', ['audience_type' => $this->audienceType], $this->report->organization_id);

        $this->audienceOrganizationId = null;
        $this->audienceDepartmentId = null;
        $this->audienceRoleName = '';
        $this->audiencePersonId = null;
        $this->audiencePersonSearch = '';
        session()->flash('success', 'Audience rule added.');
    }

    public function removeAudience(int $audienceId): void
    {
        $this->authorize('update', $this->report);
        AuditReportAudience::findOrFail($audienceId)->delete();

        AuditLogger::record($this->report, 'audit_report.visibility_changed', ['removed' => $audienceId], $this->report->organization_id);
        session()->flash('success', 'Audience rule removed.');
    }

    public function render()
    {
        $this->report->refresh();

        return view('livewire.audit-reports.audit-report-detail', [
            'documentsByType' => $this->report->documents()->orderByDesc('version_number')->get()->groupBy('document_type'),
            'audiences' => $this->report->audiences()->with(['organization', 'department', 'person'])->get(),
            'organizations' => Organization::where('is_active', true)->orderBy('display_name')->get(['id', 'display_name', 'legal_name']),
            'departments' => Department::where('organization_id', $this->report->organization_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
