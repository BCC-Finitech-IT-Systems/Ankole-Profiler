<?php

namespace App\Livewire\AuditReports;

use App\Models\AuditReport;
use App\Models\Department;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AuditReportsManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $auditTypeFilter = '';
    public string $statusFilter = '';
    public ?int $departmentFilter = null;

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $confirmingArchiveId = null;

    public ?int $organization_id = null;
    public ?int $department_id = null;
    public string $audited_institution_name = '';
    public string $title = '';
    public string $audit_type = 'internal';
    public string $period_start = '';
    public string $period_end = '';
    public string $issuing_body = '';
    public string $issue_date = '';
    public string $status = 'draft';
    public string $overall_rating = '';
    public string $summary = '';
    public bool $restricted = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'auditTypeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingAuditTypeFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingDepartmentFilter(): void { $this->resetPage(); }

    public function updatedOrganizationId(): void
    {
        $this->department_id = null;
    }

    public function create(): void
    {
        $this->authorize('create', AuditReport::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $report = AuditReport::findOrFail($id);
        $this->authorize('update', $report);

        $this->editingId = $id;
        $this->organization_id = $report->organization_id;
        $this->department_id = $report->department_id;
        $this->audited_institution_name = $report->audited_institution_name ?? '';
        $this->title = $report->title;
        $this->audit_type = $report->audit_type;
        $this->period_start = $report->period_start?->format('Y-m-d') ?? '';
        $this->period_end = $report->period_end?->format('Y-m-d') ?? '';
        $this->issuing_body = $report->issuing_body;
        $this->issue_date = $report->issue_date?->format('Y-m-d') ?? '';
        $this->status = $report->status;
        $this->overall_rating = $report->overall_rating ?? '';
        $this->summary = $report->summary ?? '';
        $this->restricted = $report->restricted;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->organization_id = null;
        $this->department_id = null;
        $this->audited_institution_name = '';
        $this->title = '';
        $this->audit_type = 'internal';
        $this->period_start = '';
        $this->period_end = '';
        $this->issuing_body = '';
        $this->issue_date = '';
        $this->status = 'draft';
        $this->overall_rating = '';
        $this->summary = '';
        $this->restricted = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $user = Auth::user();
        $report = $this->editingId ? AuditReport::findOrFail($this->editingId) : null;

        $this->authorize($report ? 'update' : 'create', $report ?? AuditReport::class);

        $allowedOrgIds = $this->organizationOptions()->pluck('id');

        $this->validate([
            'organization_id' => ['required', Rule::in($allowedOrgIds)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('organization_id', $this->organization_id)],
            'title' => 'required|string|max:255',
            'audit_type' => 'required|in:internal,external,financial,compliance,operational,institutional',
            'issuing_body' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'status' => 'required|in:draft,issued,under_review,closed',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $data = [
            'organization_id' => $this->organization_id,
            'department_id' => $this->department_id,
            'audited_institution_name' => $this->audited_institution_name ?: null,
            'title' => $this->title,
            'audit_type' => $this->audit_type,
            'period_start' => $this->period_start ?: null,
            'period_end' => $this->period_end ?: null,
            'issuing_body' => $this->issuing_body,
            'issue_date' => $this->issue_date,
            'status' => $this->status,
            'overall_rating' => $this->overall_rating ?: null,
            'summary' => $this->summary ?: null,
            'restricted' => $this->restricted,
            'updated_by' => $user->id,
        ];

        if ($report) {
            $report->update($data);
            AuditLogger::record($report, 'audit_report.updated', [], $report->organization_id);
            session()->flash('success', 'Audit report updated.');
        } else {
            $data['created_by'] = $user->id;
            $report = AuditReport::create($data);
            AuditLogger::record($report, 'audit_report.created', [], $report->organization_id);
            session()->flash('success', 'Audit report recorded.');
        }

        $this->closeModal();
    }

    public function confirmArchive(int $id): void
    {
        $report = AuditReport::findOrFail($id);
        $this->authorize('archive', $report);
        $this->confirmingArchiveId = $id;
    }

    public function cancelArchive(): void
    {
        $this->confirmingArchiveId = null;
    }

    public function archive(): void
    {
        $report = AuditReport::findOrFail($this->confirmingArchiveId);
        $this->authorize('archive', $report);

        $report->delete();
        AuditLogger::record($report, 'audit_report.archived', [], $report->organization_id);

        $this->confirmingArchiveId = null;
        session()->flash('success', 'Audit report archived.');
    }

    private function organizationOptions()
    {
        $user = Auth::user();
        $directIds = $user->managedOrganizationIds();
        $viaDepartments = Department::whereIn('id', $user->managedDepartmentIds())->pluck('organization_id');

        return Organization::whereIn('id', $directIds->merge($viaDepartments)->unique())->orderBy('display_name')->get(['id', 'display_name', 'legal_name']);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-audit-reports'), 403);

        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        $query = AuditReport::with(['department', 'organization', 'followUpOwner'])
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->search($this->search)
            ->when($this->auditTypeFilter, fn ($q) => $q->where('audit_type', $this->auditTypeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderByDesc('issue_date');

        return view('livewire.audit-reports.audit-reports-management', [
            'reports' => $query->paginate(15),
            'organizations' => $this->organizationOptions(),
            'departments' => Department::whereIn('id', $deptIds)->orWhereIn('organization_id', $orgIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
