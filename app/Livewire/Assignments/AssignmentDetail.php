<?php

namespace App\Livewire\Assignments;

use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\AssignmentProgressUpdate;
use App\Notifications\AssignmentReviewDecision;
use App\Notifications\AssignmentStatusChanged;
use App\Services\AssignmentNotificationTargets;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class AssignmentDetail extends Component
{
    use WithFileUploads;

    public Assignment $assignment;

    // Progress form
    public bool $showProgressForm = false;
    public string $progress_reported_on = '';
    public string $progress_percent_complete = '';
    public string $progressStatus = 'in_progress';
    public string $notes = '';
    public string $blockers = '';
    public string $next_steps = '';
    public string $time_spent_minutes = '';
    public string $revised_due_date = '';
    public $evidence;

    // Review
    public string $review_comment = '';
    public string $closeStatus = 'cancelled';

    public function mount(Assignment $assignment): void
    {
        $this->authorize('view', $assignment);
        $this->assignment = $assignment;
    }

    // ─── Progress ─────────────────────────────────────────────────────────────

    public function openProgressForm(): void
    {
        $this->authorize('reportProgress', $this->assignment);

        $this->progress_reported_on = now()->toDateString();
        $this->progress_percent_complete = (string) $this->assignment->percent_complete;
        $this->progressStatus = $this->assignment->status === 'not_started' ? 'in_progress' : $this->assignment->status;
        $this->notes = '';
        $this->blockers = '';
        $this->next_steps = '';
        $this->time_spent_minutes = '';
        $this->revised_due_date = '';
        $this->showProgressForm = true;
    }

    public function closeProgressForm(): void
    {
        $this->showProgressForm = false;
        $this->resetValidation();
    }

    public function recordProgress(): void
    {
        $this->authorize('reportProgress', $this->assignment);

        $this->validate([
            'progress_reported_on' => 'required|date',
            'progress_percent_complete' => 'required|integer|min:0|max:100',
            'progressStatus' => 'required|in:not_started,in_progress,blocked,awaiting_review',
            'notes' => 'nullable|string',
            'blockers' => 'nullable|string',
            'next_steps' => 'nullable|string',
            'time_spent_minutes' => 'nullable|integer|min:0',
            'revised_due_date' => 'nullable|date',
        ]);

        $before = Assignment::trackedFieldSnapshot($this->assignment);
        $wasSubmittedForReview = $this->progressStatus === 'awaiting_review';

        DB::transaction(function () {
            AssignmentProgressUpdate::create([
                'assignment_id' => $this->assignment->id,
                'reported_on' => $this->progress_reported_on,
                'percent_complete' => $this->progress_percent_complete,
                'notes' => $this->notes ?: null,
                'blockers' => $this->blockers ?: null,
                'next_steps' => $this->next_steps ?: null,
                'time_spent_minutes' => $this->time_spent_minutes !== '' ? $this->time_spent_minutes : null,
                'revised_due_date' => $this->revised_due_date ?: null,
                'reported_by' => Auth::id(),
            ]);

            $this->assignment->update([
                'status' => $this->progressStatus,
                'percent_complete' => $this->progress_percent_complete,
                'revised_due_date' => $this->revised_due_date ?: $this->assignment->revised_due_date,
                'updated_by' => Auth::id(),
            ]);
        });

        $this->assignment->logFieldChanges($before);
        AuditLogger::record($this->assignment, 'assignment.progress_recorded', [
            'percent_complete' => $this->progress_percent_complete,
            'status' => $this->progressStatus,
        ], $this->assignment->organization_id);

        $this->notifyStatusChange();

        session()->flash('success', $wasSubmittedForReview ? 'Submitted for review.' : 'Progress recorded.');
        $this->closeProgressForm();
    }

    public function uploadEvidence(int $progressUpdateId): void
    {
        $progressUpdate = AssignmentProgressUpdate::findOrFail($progressUpdateId);
        $this->authorize('reportProgress', $this->assignment);

        $this->validate(['evidence' => 'required|file|max:20480']);

        $path = $this->evidence->store('assignments/evidence/' . $progressUpdate->id, 'local');

        AssignmentDocument::create([
            'documentable_type' => AssignmentProgressUpdate::class,
            'documentable_id' => $progressUpdate->id,
            'kind' => 'evidence',
            'title' => $this->evidence->getClientOriginalName(),
            'path' => $path,
            'original_name' => $this->evidence->getClientOriginalName(),
            'mime' => $this->evidence->getMimeType(),
            'size' => $this->evidence->getSize(),
            'hash' => hash_file('sha256', $this->evidence->getRealPath()),
            'uploaded_by' => Auth::id(),
        ]);

        $this->evidence = null;
        session()->flash('success', 'Evidence uploaded.');
    }

    // ─── Review workflow ────────────────────────────────────────────────────────

    public function accept(): void
    {
        $this->authorize('review', $this->assignment);

        $before = Assignment::trackedFieldSnapshot($this->assignment);

        $this->assignment->update([
            'status' => 'completed',
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'review_comment' => $this->review_comment ?: null,
        ]);

        $this->assignment->logFieldChanges($before);
        AuditLogger::record($this->assignment, 'assignment.accepted', ['comment' => $this->review_comment], $this->assignment->organization_id);
        $this->notifyReviewDecision('accepted');

        $this->review_comment = '';
        session()->flash('success', 'Assignment accepted and closed as completed.');
    }

    public function returnForRevision(): void
    {
        $this->authorize('review', $this->assignment);

        $this->validate(['review_comment' => 'required|string'], [], ['review_comment' => 'reason']);

        $before = Assignment::trackedFieldSnapshot($this->assignment);

        $this->assignment->update([
            'status' => 'in_progress',
            'review_comment' => $this->review_comment,
        ]);

        $this->assignment->logFieldChanges($before);
        AuditLogger::record($this->assignment, 'assignment.returned', ['comment' => $this->review_comment], $this->assignment->organization_id);
        $this->notifyReviewDecision('returned');

        $this->review_comment = '';
        session()->flash('success', 'Assignment returned for more work.');
    }

    public function close(): void
    {
        $this->authorize('review', $this->assignment);

        $this->validate([
            'review_comment' => 'required|string',
            'closeStatus' => 'required|in:cancelled,deferred',
        ], [], ['review_comment' => 'reason']);

        $before = Assignment::trackedFieldSnapshot($this->assignment);

        $this->assignment->update([
            'status' => $this->closeStatus,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'review_comment' => $this->review_comment,
        ]);

        $this->assignment->logFieldChanges($before);
        AuditLogger::record($this->assignment, 'assignment.closed', [
            'status' => $this->closeStatus, 'comment' => $this->review_comment,
        ], $this->assignment->organization_id);
        $this->notifyReviewDecision('closed');

        $this->review_comment = '';
        session()->flash('success', 'Assignment closed.');
    }

    private function notifyStatusChange(): void
    {
        foreach (AssignmentNotificationTargets::forAssignment($this->assignment) as $recipient) {
            $recipient->notify(new AssignmentStatusChanged($this->assignment));
        }
    }

    private function notifyReviewDecision(string $decision): void
    {
        foreach (AssignmentNotificationTargets::forAssignment($this->assignment) as $recipient) {
            $recipient->notify(new AssignmentReviewDecision($this->assignment, $decision));
        }
    }

    public function render()
    {
        $this->assignment->refresh();

        return view('livewire.assignments.assignment-detail', [
            'progressUpdates' => $this->assignment->progressUpdates()->with(['reporter', 'evidence'])->get(),
            'supportPeople' => $this->assignment->supportPeople,
            'watchers' => $this->assignment->watchers,
        ]);
    }
}
