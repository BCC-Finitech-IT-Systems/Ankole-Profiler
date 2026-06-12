<?php

namespace App\Policies;

use App\Models\RoleType;
use App\Models\User;

class RoleTypePolicy
{
    /**
     * Department-scoped role types are managed by whoever administers that
     * department or its organization. Global role types (no department)
     * are reserved for Super Admin, which Gate::before short-circuits.
     */
    public function manage(User $user, RoleType $roleType): bool
    {
        if (!$user->can('manage-role-types')) {
            return false;
        }

        if ($roleType->department_id) {
            return $user->managedDepartmentIds()->contains($roleType->department_id)
                || ($roleType->department && $user->managedOrganizationIds()->contains($roleType->department->organization_id));
        }

        if ($roleType->organization_id) {
            return $user->managedOrganizationIds()->contains($roleType->organization_id);
        }

        return false;
    }
}
