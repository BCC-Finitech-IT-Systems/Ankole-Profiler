<?php

namespace App\Policies;

use App\Models\AuditDocument;
use App\Models\User;

class AuditDocumentPolicy
{
    public function download(User $user, AuditDocument $document): bool
    {
        return $user->can('download-audit-documents') && $document->report->isVisibleTo($user);
    }
}
