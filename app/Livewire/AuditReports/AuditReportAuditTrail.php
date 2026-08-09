<?php

namespace App\Livewire\AuditReports;

use App\Models\AuditLog;
use App\Models\AuditReport;
use Livewire\Component;
use Livewire\WithPagination;

class AuditReportAuditTrail extends Component
{
    use WithPagination;

    public AuditReport $report;

    public function mount(AuditReport $report): void
    {
        // view-audit-logs alone isn't diocese-scoped, so also require the
        // caller to be authorized against this specific report's diocese —
        // otherwise any Organization Admin in any diocese could read
        // another diocese's audit trail by guessing a report id.
        $this->authorize('viewAny', AuditLog::class);
        $this->authorize('view', $report);
        $this->report = $report;
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->where('auditable_type', AuditReport::class)
            ->where('auditable_id', $this->report->id)
            ->with('actor')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.audit-reports.audit-report-audit-trail', [
            'logs' => $logs,
        ]);
    }
}
