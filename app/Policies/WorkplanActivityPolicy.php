<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkplanActivity;

class WorkplanActivityPolicy
{
    public function recordProgress(User $user, WorkplanActivity $activity): bool
    {
        if (!$user->can('record-workplan-progress')) {
            return false;
        }

        $workplan = $activity->workplan;

        if (!in_array($workplan->status, ['approved', 'in_progress'], true)) {
            return false;
        }

        if ($user->managedOrganizationIds()->contains($workplan->organization_id)) {
            return true;
        }

        return $user->managedDepartmentIds()->contains($workplan->department_id);
    }

    public function uploadDocument(User $user, WorkplanActivity $activity): bool
    {
        return $user->can('upload-workplan-documents') && $this->view($user, $activity);
    }

    public function download(User $user, WorkplanActivity $activity): bool
    {
        return $user->can('download-workplan-documents') && $this->view($user, $activity);
    }

    private function view(User $user, WorkplanActivity $activity): bool
    {
        if (!$user->can('view-workplans')) {
            return false;
        }

        $workplan = $activity->workplan;

        if ($user->managedOrganizationIds()->contains($workplan->organization_id)) {
            return true;
        }

        return $user->managedDepartmentIds()->contains($workplan->department_id);
    }
}
