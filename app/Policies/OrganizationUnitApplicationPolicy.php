<?php

namespace App\Policies;

use App\Models\OrganizationUnitApplication;
use App\Models\User;

class OrganizationUnitApplicationPolicy
{
    public function approve(User $user, OrganizationUnitApplication $application): bool
    {
        if (!$user->can('approve-unit-membership')) {
            return false;
        }

        return $this->managesApplicationScope($user, $application);
    }

    public function reject(User $user, OrganizationUnitApplication $application): bool
    {
        return $this->approve($user, $application);
    }

    /**
     * The approver must administer the application's organization, or the
     * department the applied-for unit belongs to.
     */
    private function managesApplicationScope(User $user, OrganizationUnitApplication $application): bool
    {
        if ($user->managedOrganizationIds()->contains($application->organization_id)) {
            return true;
        }

        $departmentId = $application->unit?->department_id;

        return $departmentId && $user->managedDepartmentIds()->contains($departmentId);
    }
}
