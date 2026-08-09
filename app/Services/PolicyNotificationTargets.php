<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PersonAffiliation;
use Illuminate\Support\Collection;

class PolicyNotificationTargets
{
    /**
     * Users to notify for policy events affecting $organization (an
     * institution): active-affiliation users able to act on adoption,
     * de-duplicated. Falls back to nothing if the institution has no
     * linked user accounts yet — notification delivery is best-effort.
     */
    public static function forInstitution(Organization $organization): Collection
    {
        return PersonAffiliation::where('organization_id', $organization->id)
            ->where('status', 'active')
            ->with('person.user')
            ->get()
            ->map(fn (PersonAffiliation $affiliation) => $affiliation->person?->user)
            ->filter()
            ->filter(fn ($user) => $user->can('view-policy-adoption'))
            ->unique('id')
            ->values();
    }
}
