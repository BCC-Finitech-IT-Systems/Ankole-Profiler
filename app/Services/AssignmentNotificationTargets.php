<?php

namespace App\Services;

use App\Models\Assignment;
use Illuminate\Support\Collection;

class AssignmentNotificationTargets
{
    /**
     * Users to notify for assignment events: the lead, every support
     * assignee, and every watcher, mapped to their linked User accounts and
     * deduplicated — the same ->unique('id') step PolicyNotificationTargets
     * uses, since a person can appear in more than one of these three sets.
     */
    public static function forAssignment(Assignment $assignment): Collection
    {
        $people = collect([$assignment->responsiblePerson])
            ->merge($assignment->supportPeople)
            ->merge($assignment->watchers)
            ->filter();

        return $people
            ->map(fn ($person) => $person->user ?? null)
            ->filter()
            ->unique('id')
            ->values();
    }
}
