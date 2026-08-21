<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

/**
 * Authorises actions on an Organization. Super Admin is short-circuited by
 * the Gate::before hook in AppServiceProvider, so these methods only ever
 * run for non-super users.
 *
 * Every check pairs a capability with a scope: holding the permission is not
 * enough on its own, the user must also manage (or at least be able to reach)
 * the organization in question. Without the scope half, any user carrying a
 * broadly-granted permission could act on organizations they cannot see.
 */
class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->canAccessOrganization($organization->id);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can('edit-organizations')
            && $user->managedOrganizationIds()->contains($organization->id);
    }

    public function delete(User $user, Organization $organization): bool
    {
        if (!$user->can('delete-organizations')) {
            return false;
        }

        // A diocese or head office with members underneath it should not be
        // removable in one click; clear the children first.
        if ($organization->childOrganizations()->exists()) {
            return false;
        }

        return $user->managedOrganizationIds()->contains($organization->id);
    }
}
