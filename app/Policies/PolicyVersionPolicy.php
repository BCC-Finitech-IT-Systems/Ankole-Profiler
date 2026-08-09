<?php

namespace App\Policies;

use App\Models\PolicyVersion;
use App\Models\User;

class PolicyVersionPolicy
{
    public function view(User $user, PolicyVersion $version): bool
    {
        if (!$user->can('view-policies')) {
            return false;
        }

        return $version->isVisibleTo($user);
    }

    public function createRevision(User $user, PolicyVersion $version): bool
    {
        return $user->can('create-policies')
            && $this->inDioceseOrDepartmentScope($user, $version)
            && $version->policy->status !== 'archived';
    }

    public function update(User $user, PolicyVersion $version): bool
    {
        return $user->can('edit-policies')
            && $this->inDioceseOrDepartmentScope($user, $version)
            && $version->isEditable();
    }

    public function recordApproval(User $user, PolicyVersion $version): bool
    {
        return $user->can('approve-policies') && $this->inDioceseScope($user, $version);
    }

    public function approve(User $user, PolicyVersion $version): bool
    {
        return $user->can('approve-policies')
            && $this->inDioceseScope($user, $version)
            && $version->hasSynodApproval();
    }

    public function publish(User $user, PolicyVersion $version): bool
    {
        return $user->can('publish-policies')
            && $this->inDioceseScope($user, $version)
            && $version->isPublishable();
    }

    public function archive(User $user, PolicyVersion $version): bool
    {
        return $user->can('archive-policies') && $this->inDioceseOrDepartmentScope($user, $version);
    }

    public function download(User $user, PolicyVersion $version): bool
    {
        return $user->can('download-policy-documents') && $this->view($user, $version);
    }

    private function inDioceseScope(User $user, PolicyVersion $version): bool
    {
        return $user->managedOrganizationIds()->contains($version->policy->organization_id);
    }

    private function inDioceseOrDepartmentScope(User $user, PolicyVersion $version): bool
    {
        if ($this->inDioceseScope($user, $version)) {
            return true;
        }

        $departmentId = $version->policy->department_id;

        return $departmentId && $user->managedDepartmentIds()->contains($departmentId);
    }
}
