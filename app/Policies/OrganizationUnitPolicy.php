<?php

namespace App\Policies;

use App\Models\OrganizationUnit;
use App\Models\User;

class OrganizationUnitPolicy
{
    /**
     * Manage a unit's membership and roles. Requires the capability AND a
     * management scope over the unit's organization or department; Super
     * Admin is short-circuited by Gate::before.
     */
    public function manage(User $user, OrganizationUnit $unit): bool
    {
        if (!$user->can('edit-units')) {
            return false;
        }

        return $user->managedOrganizationIds()->contains($unit->organization_id)
            || ($unit->department_id && $user->managedDepartmentIds()->contains($unit->department_id));
    }

    public function view(User $user, OrganizationUnit $unit): bool
    {
        return $user->canAccessOrganization($unit->organization_id);
    }
}
