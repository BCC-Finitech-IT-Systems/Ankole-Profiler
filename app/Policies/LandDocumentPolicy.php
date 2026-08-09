<?php

namespace App\Policies;

use App\Models\LandDocument;
use App\Models\User;

class LandDocumentPolicy
{
    public function view(User $user, LandDocument $document): bool
    {
        return $user->can('view-land-parcels') && $document->isVisibleTo($user);
    }

    public function download(User $user, LandDocument $document): bool
    {
        return $user->can('download-land-documents') && $document->isVisibleTo($user);
    }

    public function upload(User $user, LandDocument $document): bool
    {
        if (!$user->can('upload-land-documents')) {
            return false;
        }

        $parcel = $document->parcel;

        if ($user->managedOrganizationIds()->contains($parcel->organization_id)) {
            return true;
        }

        return $parcel->department_id && $user->managedDepartmentIds()->contains($parcel->department_id);
    }
}
