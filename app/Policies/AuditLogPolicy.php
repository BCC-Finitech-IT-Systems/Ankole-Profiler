<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-audit-logs');
    }

    public function view(User $user, AuditLog $log): bool
    {
        if (!$user->can('view-audit-logs')) {
            return false;
        }

        if (!$log->organization_id) {
            return true;
        }

        return $user->managedOrganizationIds()->contains($log->organization_id);
    }
}
