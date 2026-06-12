<?php

namespace App\Policies;

use App\Models\PersonAffiliation;
use App\Models\User;

class PersonAffiliationPolicy
{
    /**
     * Decide a pending organization membership application.
     */
    public function approveMembership(User $user, PersonAffiliation $affiliation): bool
    {
        if (!$user->can('approve-organization-membership')) {
            return false;
        }

        return $user->managedOrganizationIds()->contains($affiliation->organization_id);
    }

    public function rejectMembership(User $user, PersonAffiliation $affiliation): bool
    {
        return $this->approveMembership($user, $affiliation);
    }
}
