<?php

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;

class PolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-policies');
    }

    public function view(User $user, Policy $policy): bool
    {
        if (!$user->can('view-policies')) {
            return false;
        }

        if ($this->inDioceseOrDepartmentScope($user, $policy)) {
            return true;
        }

        $institutionIds = $user->activeAffiliations()->pluck('person_affiliations.organization_id')->unique();

        return $policy->publications()->whereIn('organization_id', $institutionIds)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create-policies');
    }

    public function update(User $user, Policy $policy): bool
    {
        return $user->can('edit-policies') && $this->inDioceseOrDepartmentScope($user, $policy);
    }

    public function archive(User $user, Policy $policy): bool
    {
        return $user->can('archive-policies') && $this->inDioceseOrDepartmentScope($user, $policy);
    }

    private function inDioceseOrDepartmentScope(User $user, Policy $policy): bool
    {
        if ($user->managedOrganizationIds()->contains($policy->organization_id)) {
            return true;
        }

        return $policy->department_id && $user->managedDepartmentIds()->contains($policy->department_id);
    }
}
