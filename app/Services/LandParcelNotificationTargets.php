<?php

namespace App\Services;

use App\Models\LandParcel;
use Illuminate\Support\Collection;

class LandParcelNotificationTargets
{
    /**
     * Users to notify for a parcel: the responsible person's linked User,
     * plus the custodian department's head, deduplicated — same ->unique('id')
     * shape as AssignmentNotificationTargets/PolicyNotificationTargets.
     */
    public static function forParcel(LandParcel $parcel): Collection
    {
        return collect([
            $parcel->responsiblePerson?->user,
            $parcel->department?->admin,
        ])
            ->filter()
            ->unique('id')
            ->values();
    }
}
