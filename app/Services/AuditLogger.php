<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Append an immutable audit log row. $actorUserId defaults to the
     * current authenticated user; pass null explicitly for system/scheduled
     * events (e.g. the overdue-deadline command) that have no actor.
     */
    public static function record(
        Model $auditable,
        string $event,
        array $properties = [],
        ?int $organizationId = null,
        ?int $subjectOrganizationId = null,
        int|false|null $actorUserId = false,
    ): AuditLog {
        return AuditLog::create([
            'organization_id' => $organizationId,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'actor_user_id' => $actorUserId === false ? Auth::id() : $actorUserId,
            'subject_organization_id' => $subjectOrganizationId,
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
