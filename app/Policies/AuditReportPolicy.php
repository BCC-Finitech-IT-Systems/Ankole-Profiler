<?php

namespace App\Policies;

use App\Models\AuditReport;
use App\Models\User;

class AuditReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-audit-reports');
    }

    public function view(User $user, AuditReport $report): bool
    {
        return $user->can('view-audit-reports') && $report->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can('create-audit-reports');
    }

    public function update(User $user, AuditReport $report): bool
    {
        return $user->can('edit-audit-reports') && $this->inScope($user, $report);
    }

    public function archive(User $user, AuditReport $report): bool
    {
        return $user->can('archive-audit-reports') && $this->inScope($user, $report);
    }

    private function inScope(User $user, AuditReport $report): bool
    {
        if ($user->managedOrganizationIds()->contains($report->organization_id)) {
            return true;
        }

        return $report->department_id && $user->managedDepartmentIds()->contains($report->department_id);
    }
}
