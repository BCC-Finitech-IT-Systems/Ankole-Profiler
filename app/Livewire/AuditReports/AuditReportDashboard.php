<?php

namespace App\Livewire\AuditReports;

use App\Models\AuditReport;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AuditReportDashboard extends Component
{
    public ?int $departmentFilter = null;
    public string $auditTypeFilter = '';
    public string $statusFilter = '';

    protected $queryString = [
        'departmentFilter' => ['except' => ''],
        'auditTypeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    private function scopedQuery()
    {
        $user = Auth::user();
        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        return AuditReport::query()
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->auditTypeFilter, fn ($q) => $q->where('audit_type', $this->auditTypeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-audit-dashboard'), 403);

        $reports = $this->scopedQuery()->with('department')->get();

        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();
        $departmentIds = Department::whereIn('organization_id', $orgIds)->pluck('id')->merge($deptIds)->unique();

        return view('livewire.audit-reports.audit-report-dashboard', [
            'total' => $reports->count(),
            'draft' => $reports->where('status', 'draft')->count(),
            'issued' => $reports->where('status', 'issued')->count(),
            'underReview' => $reports->where('status', 'under_review')->count(),
            'closed' => $reports->where('status', 'closed')->count(),
            'restrictedCount' => $reports->where('restricted', true)->count(),
            'byType' => $reports->groupBy('audit_type')->map->count(),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
