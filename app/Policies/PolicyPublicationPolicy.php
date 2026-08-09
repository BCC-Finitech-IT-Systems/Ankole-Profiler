<?php

namespace App\Policies;

use App\Models\PolicyPublication;
use App\Models\User;

class PolicyPublicationPolicy
{
    public function view(User $user, PolicyPublication $publication): bool
    {
        if (!$user->can('view-policy-adoption')) {
            return false;
        }

        if ($user->managedOrganizationIds()->contains($publication->policy->organization_id)) {
            return true;
        }

        return $user->canAccessOrganization($publication->organization_id);
    }

    public function updateAdoption(User $user, PolicyPublication $publication): bool
    {
        if (!$user->can('update-policy-adoption')) {
            return false;
        }

        if (!$this->isInstitutionSideAuthorized($user, $publication)) {
            return false;
        }

        return $publication->policyVersion->status === 'published';
    }

    public function acknowledge(User $user, PolicyPublication $publication): bool
    {
        return $this->updateAdoption($user, $publication) && $publication->status === 'sent';
    }

    public function requestException(User $user, PolicyPublication $publication): bool
    {
        return $this->updateAdoption($user, $publication);
    }

    /**
     * An institution can never decide its own exception request — this is
     * diocese-scope only, deliberately not delegated to canAccessOrganization.
     */
    public function decideException(User $user, PolicyPublication $publication): bool
    {
        return $user->can('decide-policy-exceptions')
            && $user->managedOrganizationIds()->contains($publication->policy->organization_id);
    }

    private function isInstitutionSideAuthorized(User $user, PolicyPublication $publication): bool
    {
        if ($user->managedOrganizationIds()->contains($publication->organization_id)) {
            return true;
        }

        return $user->canAccessOrganization($publication->organization_id);
    }
}
