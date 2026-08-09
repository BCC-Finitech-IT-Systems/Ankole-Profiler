<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-assignments');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if (!$user->can('view-assignments')) {
            return false;
        }

        return $this->inManagerScope($user, $assignment) || $this->isAssignee($user, $assignment);
    }

    public function create(User $user): bool
    {
        return $user->can('create-assignments');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('edit-assignments') && $this->inManagerScope($user, $assignment) && $assignment->isEditable();
    }

    public function close(User $user, Assignment $assignment): bool
    {
        return $user->can('close-assignments') && $this->inManagerScope($user, $assignment);
    }

    /**
     * The genuinely new authorization dimension for this feature: the
     * acting user might not be an admin/manager at all, just the assignee
     * themselves reporting their own progress.
     */
    public function reportProgress(User $user, Assignment $assignment): bool
    {
        if (!$user->can('report-assignment-progress')) {
            return false;
        }

        if (!in_array($assignment->status, ['not_started', 'in_progress', 'blocked'], true)) {
            return false;
        }

        return $this->inManagerScope($user, $assignment) || $this->isAssignee($user, $assignment);
    }

    /**
     * Review requires manager scope specifically — never assignee scope, so
     * an assignee can never review (accept/return/close) their own work.
     */
    public function review(User $user, Assignment $assignment): bool
    {
        return $user->can('review-assignments')
            && $this->inManagerScope($user, $assignment)
            && $assignment->status === 'awaiting_review';
    }

    private function inManagerScope(User $user, Assignment $assignment): bool
    {
        if ($user->managedOrganizationIds()->contains($assignment->organization_id)) {
            return true;
        }

        return $assignment->department_id && $user->managedDepartmentIds()->contains($assignment->department_id);
    }

    private function isAssignee(User $user, Assignment $assignment): bool
    {
        $personId = $user->personId();

        if (!$personId) {
            return false;
        }

        if ($assignment->responsible_person_id === $personId) {
            return true;
        }

        return $assignment->supportPeople()->where('persons.id', $personId)->exists();
    }
}
