<?php

namespace App\Policies;

use App\Models\LandParcel;
use App\Models\User;

class LandParcelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-land-parcels');
    }

    public function view(User $user, LandParcel $parcel): bool
    {
        return $user->can('view-land-parcels') && $this->inScope($user, $parcel);
    }

    public function create(User $user): bool
    {
        return $user->can('create-land-parcels');
    }

    public function update(User $user, LandParcel $parcel): bool
    {
        return $user->can('edit-land-parcels') && $this->inScope($user, $parcel);
    }

    public function recordUpdate(User $user, LandParcel $parcel): bool
    {
        return $user->can('edit-land-parcels') && $this->inScope($user, $parcel);
    }

    public function recordPayment(User $user, LandParcel $parcel): bool
    {
        return $user->can('edit-land-parcels') && $this->inScope($user, $parcel);
    }

    public function archive(User $user, LandParcel $parcel): bool
    {
        return $user->can('archive-land-parcels') && $this->inScope($user, $parcel);
    }

    /**
     * Disputes are legally sensitive and gated at organization scope only —
     * a Department Manager cannot mark or resolve a dispute on their own
     * department's parcel, mirroring decide-policy-exceptions.
     */
    public function markDisputed(User $user, LandParcel $parcel): bool
    {
        return $user->can('manage-land-disputes') && $user->managedOrganizationIds()->contains($parcel->organization_id);
    }

    public function resolveDispute(User $user, LandParcel $parcel): bool
    {
        return $this->markDisputed($user, $parcel);
    }

    private function inScope(User $user, LandParcel $parcel): bool
    {
        if ($user->managedOrganizationIds()->contains($parcel->organization_id)) {
            return true;
        }

        return $parcel->department_id && $user->managedDepartmentIds()->contains($parcel->department_id);
    }
}
