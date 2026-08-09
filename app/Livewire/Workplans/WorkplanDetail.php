<?php

namespace App\Livewire\Workplans;

use App\Models\Person;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use App\Models\WorkplanDocument;
use App\Models\WorkplanProgressUpdate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class WorkplanDetail extends Component
{
    use WithFileUploads;

    public Workplan $workplan;

    // Activity modal
    public bool $showActivityModal = false;
    public ?int $editingActivityId = null;
    public string $strategic_objective = '';
    public string $activity = '';
    public string $expected_output = '';
    public string $performance_indicator = '';
    public string $baseline = '';
    public string $target = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $priority = 'medium';
    public string $budget_estimate = '';
    public string $funding_source = '';
    public ?int $responsible_person_id = null;
    public string $responsiblePersonSearch = '';
    public string $responsiblePersonSelectedName = '';
    public array $personResults = [];
    public string $responsible_team = '';
    public string $dependencies = '';

    // Submit / decision
    public string $review_comment = '';
    public string $decision_comment = '';

    // Progress
    public ?int $activeProgressActivityId = null;
    public string $progress_reported_on = '';
    public string $progress_percent_complete = '';
    public string $work_completed = '';
    public string $pending_work = '';
    public string $challenges = '';
    public string $corrective_action = '';
    public string $expenditure = '';
    public string $progressStatus = 'in_progress';
    public $evidence;

    public function mount(Workplan $workplan): void
    {
        $this->authorize('view', $workplan);
        $this->workplan = $workplan;
    }

    // ─── Activities ───────────────────────────────────────────────────────────

    public function openActivityModal(?int $activityId = null): void
    {
        $this->authorize('update', $this->workplan);
        $this->resetActivityForm();

        if ($activityId) {
            $act = WorkplanActivity::findOrFail($activityId);
            $this->editingActivityId = $act->id;
            $this->strategic_objective = $act->strategic_objective;
            $this->activity = $act->activity;
            $this->expected_output = $act->expected_output ?? '';
            $this->performance_indicator = $act->performance_indicator ?? '';
            $this->baseline = $act->baseline ?? '';
            $this->target = $act->target ?? '';
            $this->start_date = $act->start_date?->format('Y-m-d') ?? '';
            $this->end_date = $act->end_date?->format('Y-m-d') ?? '';
            $this->priority = $act->priority;
            $this->budget_estimate = (string) ($act->budget_estimate ?? '');
            $this->funding_source = $act->funding_source ?? '';
            $this->responsible_person_id = $act->responsible_person_id;
            $this->responsiblePersonSelectedName = $act->responsiblePerson?->full_name ?? '';
            $this->responsiblePersonSearch = $this->responsiblePersonSelectedName;
            $this->responsible_team = $act->responsible_team ?? '';
            $this->dependencies = $act->dependencies ?? '';
        }

        $this->showActivityModal = true;
    }

    public function closeActivityModal(): void
    {
        $this->showActivityModal = false;
        $this->resetActivityForm();
    }

    private function resetActivityForm(): void
    {
        $this->editingActivityId = null;
        $this->strategic_objective = '';
        $this->activity = '';
        $this->expected_output = '';
        $this->performance_indicator = '';
        $this->baseline = '';
        $this->target = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->priority = 'medium';
        $this->budget_estimate = '';
        $this->funding_source = '';
        $this->responsible_person_id = null;
        $this->responsiblePersonSearch = '';
        $this->responsiblePersonSelectedName = '';
        $this->personResults = [];
        $this->responsible_team = '';
        $this->dependencies = '';
        $this->resetValidation();
    }

    public function updatedResponsiblePersonSearch(): void
    {
        if (strlen($this->responsiblePersonSearch) < 2) {
            $this->personResults = [];
            return;
        }

        $this->personResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->responsiblePersonSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->responsiblePersonSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->toArray();
    }

    public function selectResponsiblePerson(int $personId, string $name): void
    {
        $this->responsible_person_id = $personId;
        $this->responsiblePersonSelectedName = $name;
        $this->responsiblePersonSearch = $name;
        $this->personResults = [];
    }

    public function saveActivity(): void
    {
        $this->authorize('update', $this->workplan);

        $this->validate([
            'strategic_objective' => 'required|string|max:255',
            'activity' => 'required|string',
            'expected_output' => 'nullable|string',
            'performance_indicator' => 'nullable|string|max:255',
            'baseline' => 'nullable|string|max:255',
            'target' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'required|in:low,medium,high',
            'budget_estimate' => 'nullable|numeric|min:0',
            'funding_source' => 'nullable|string|max:255',
            'responsible_person_id' => 'nullable|exists:persons,id',
            'responsible_team' => 'nullable|string|max:255',
            'dependencies' => 'nullable|string',
        ]);

        $data = [
            'workplan_id' => $this->workplan->id,
            'strategic_objective' => $this->strategic_objective,
            'activity' => $this->activity,
            'expected_output' => $this->expected_output ?: null,
            'performance_indicator' => $this->performance_indicator ?: null,
            'baseline' => $this->baseline ?: null,
            'target' => $this->target ?: null,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'priority' => $this->priority,
            'budget_estimate' => $this->budget_estimate !== '' ? $this->budget_estimate : null,
            'funding_source' => $this->funding_source ?: null,
            'responsible_person_id' => $this->responsible_person_id,
            'responsible_team' => $this->responsible_team ?: null,
            'dependencies' => $this->dependencies ?: null,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingActivityId) {
            $act = WorkplanActivity::findOrFail($this->editingActivityId);
            $act->update($data);
        } else {
            $data['created_by'] = Auth::id();
            WorkplanActivity::create($data);
        }

        session()->flash('success', 'Activity saved.');
        $this->closeActivityModal();
    }

    public function removeActivity(int $activityId): void
    {
        $this->authorize('update', $this->workplan);
        WorkplanActivity::findOrFail($activityId)->delete();
        session()->flash('success', 'Activity removed.');
    }

    // ─── Submit / approve / reject ─────────────────────────────────────────────

    public function submit(): void
    {
        $this->authorize('submit', $this->workplan);

        $this->workplan->update([
            'status' => 'submitted',
            'review_comment' => $this->review_comment ?: null,
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);

        AuditLogger::record($this->workplan, 'workplan.submitted', [], $this->workplan->organization_id);
        session()->flash('success', 'Workplan submitted for approval.');
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->workplan);

        $this->workplan->update([
            'status' => 'approved',
            'decision_comment' => $this->decision_comment ?: null,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        AuditLogger::record($this->workplan, 'workplan.approved', [
            'comment' => $this->decision_comment,
        ], $this->workplan->organization_id);

        $this->decision_comment = '';
        session()->flash('success', 'Workplan approved.');
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->workplan);

        $this->validate(['decision_comment' => 'required|string'], [], ['decision_comment' => 'reason']);

        $this->workplan->update([
            'status' => 'draft',
            'decision_comment' => $this->decision_comment,
        ]);

        AuditLogger::record($this->workplan, 'workplan.rejected', [
            'comment' => $this->decision_comment,
        ], $this->workplan->organization_id);

        $this->decision_comment = '';
        session()->flash('success', 'Workplan returned to draft for revision.');
    }

    public function createRevision(): void
    {
        $this->authorize('createRevision', $this->workplan);

        abort_if(
            Workplan::where('department_id', $this->workplan->department_id)
                ->where('year', $this->workplan->year)
                ->whereIn('status', ['draft', 'submitted'])
                ->exists(),
            422,
            'A revision is already in progress for this department and year.'
        );

        $latestVersion = Workplan::where('department_id', $this->workplan->department_id)
            ->where('year', $this->workplan->year)
            ->max('version_number');

        $new = DB::transaction(function () use ($latestVersion) {
            $new = Workplan::create([
                'department_id' => $this->workplan->department_id,
                'organization_id' => $this->workplan->organization_id,
                'year' => $this->workplan->year,
                'version_number' => $latestVersion + 1,
                'title' => $this->workplan->title,
                'status' => 'draft',
                'supersedes_workplan_id' => $this->workplan->id,
                'created_by' => Auth::id(),
            ]);

            foreach ($this->workplan->activities as $activity) {
                WorkplanActivity::create([
                    'workplan_id' => $new->id,
                    'strategic_objective' => $activity->strategic_objective,
                    'activity' => $activity->activity,
                    'expected_output' => $activity->expected_output,
                    'performance_indicator' => $activity->performance_indicator,
                    'baseline' => $activity->baseline,
                    'target' => $activity->target,
                    'start_date' => $activity->start_date,
                    'end_date' => $activity->end_date,
                    'priority' => $activity->priority,
                    'budget_estimate' => $activity->budget_estimate,
                    'funding_source' => $activity->funding_source,
                    'responsible_person_id' => $activity->responsible_person_id,
                    'responsible_team' => $activity->responsible_team,
                    'dependencies' => $activity->dependencies,
                    'status' => $activity->status,
                    'percent_complete' => $activity->percent_complete,
                    'created_by' => Auth::id(),
                ]);
            }

            return $new;
        });

        AuditLogger::record($new, 'workplan.revision_created', ['supersedes_workplan_id' => $this->workplan->id], $this->workplan->organization_id);
        session()->flash('success', 'New draft revision created.');
        $this->redirectRoute('workplans.show', $new);
    }

    public function carryForward(): void
    {
        $this->authorize('carryForward', $this->workplan);

        $new = $this->workplan->carryForward();

        AuditLogger::record($new, 'workplan.carried_forward', ['carried_forward_from_id' => $this->workplan->id], $this->workplan->organization_id);
        session()->flash('success', 'Unfinished activities carried forward into FY' . $new->year . '.');
        $this->redirectRoute('workplans.show', $new);
    }

    // ─── Progress ─────────────────────────────────────────────────────────────

    public function openProgressForm(int $activityId): void
    {
        $activity = WorkplanActivity::findOrFail($activityId);
        $this->authorize('recordProgress', $activity);

        $this->activeProgressActivityId = $activityId;
        $this->progress_reported_on = now()->toDateString();
        $this->progress_percent_complete = (string) $activity->percent_complete;
        $this->progressStatus = $activity->status === 'not_started' ? 'in_progress' : $activity->status;
        $this->work_completed = '';
        $this->pending_work = '';
        $this->challenges = '';
        $this->corrective_action = '';
        $this->expenditure = '';
    }

    public function closeProgressForm(): void
    {
        $this->activeProgressActivityId = null;
        $this->resetValidation();
    }

    public function recordProgress(): void
    {
        abort_unless($this->activeProgressActivityId, 404);
        $activity = WorkplanActivity::findOrFail($this->activeProgressActivityId);
        $this->authorize('recordProgress', $activity);

        $this->validate([
            'progress_reported_on' => 'required|date',
            'progress_percent_complete' => 'required|integer|min:0|max:100',
            'progressStatus' => 'required|in:not_started,in_progress,completed,deferred,cancelled',
            'work_completed' => 'nullable|string',
            'pending_work' => 'nullable|string',
            'challenges' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'expenditure' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($activity) {
            WorkplanProgressUpdate::create([
                'workplan_activity_id' => $activity->id,
                'reported_on' => $this->progress_reported_on,
                'percent_complete' => $this->progress_percent_complete,
                'work_completed' => $this->work_completed ?: null,
                'pending_work' => $this->pending_work ?: null,
                'challenges' => $this->challenges ?: null,
                'corrective_action' => $this->corrective_action ?: null,
                'expenditure' => $this->expenditure !== '' ? $this->expenditure : null,
                'reported_by' => Auth::id(),
            ]);

            $activity->update([
                'status' => $this->progressStatus,
                'percent_complete' => $this->progress_percent_complete,
                'updated_by' => Auth::id(),
            ]);
        });

        AuditLogger::record($activity, 'workplan_activity.progress_recorded', [
            'percent_complete' => $this->progress_percent_complete,
            'status' => $this->progressStatus,
        ], $this->workplan->organization_id);

        session()->flash('success', 'Progress recorded.');
        $this->closeProgressForm();
    }

    public function uploadEvidence(int $progressUpdateId): void
    {
        $progressUpdate = WorkplanProgressUpdate::findOrFail($progressUpdateId);
        $this->authorize('recordProgress', $progressUpdate->activity);

        $this->validate(['evidence' => 'required|file|max:20480']);

        $path = $this->evidence->store('workplans/evidence/' . $progressUpdate->id, 'local');

        WorkplanDocument::create([
            'documentable_type' => WorkplanProgressUpdate::class,
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

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $this->workplan->refresh();

        return view('livewire.workplans.workplan-detail', [
            'activities' => $this->workplan->activities()->with(['responsiblePerson', 'progressUpdates'])->get(),
        ]);
    }
}
