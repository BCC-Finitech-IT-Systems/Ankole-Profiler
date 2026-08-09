<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workplan;

class WorkplanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-workplans');
    }

    public function view(User $user, Workplan $workplan): bool
    {
        return $user->can('view-workplans') && $this->inScope($user, $workplan);
    }

    public function create(User $user): bool
    {
        return $user->can('create-workplans');
    }

    public function update(User $user, Workplan $workplan): bool
    {
        return $user->can('edit-workplans') && $this->inScope($user, $workplan) && $workplan->isEditable();
    }

    public function submit(User $user, Workplan $workplan): bool
    {
        return ($user->can('submit-workplans') || $user->can('create-workplans'))
            && $this->inScope($user, $workplan)
            && $workplan->isEditable();
    }

    /**
     * Approve/reject require ORGANIZATION scope specifically — the
     * department that drafts a plan is never the same authority that
     * approves it (separation of duties, mirroring the Policy Repository).
     */
    public function approve(User $user, Workplan $workplan): bool
    {
        return $user->can('approve-workplans')
            && $user->managedOrganizationIds()->contains($workplan->organization_id)
            && $workplan->canBeApproved();
    }

    public function reject(User $user, Workplan $workplan): bool
    {
        return $user->can('approve-workplans')
            && $user->managedOrganizationIds()->contains($workplan->organization_id)
            && $workplan->canBeApproved();
    }

    public function archive(User $user, Workplan $workplan): bool
    {
        return $user->can('archive-workplans') && $this->inScope($user, $workplan);
    }

    public function createRevision(User $user, Workplan $workplan): bool
    {
        return $user->can('create-workplans') && $this->inScope($user, $workplan) && $workplan->status !== 'cancelled';
    }

    public function carryForward(User $user, Workplan $workplan): bool
    {
        return $user->can('carry-forward-workplans')
            && $this->inScope($user, $workplan)
            && in_array($workplan->status, ['approved', 'in_progress', 'completed', 'deferred'], true);
    }

    private function inScope(User $user, Workplan $workplan): bool
    {
        if ($user->managedOrganizationIds()->contains($workplan->organization_id)) {
            return true;
        }

        return $user->managedDepartmentIds()->contains($workplan->department_id);
    }
}
