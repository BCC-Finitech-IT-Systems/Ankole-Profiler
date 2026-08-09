<?php

namespace App\Livewire\Assignments;

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\WorkplanActivity;
use App\Notifications\AssignmentAssigned;
use App\Services\AssignmentNotificationTargets;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AssignmentsManagement extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $priorityFilter = '';
    public ?int $departmentFilter = null;
    public bool $overdueOnly = false;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $category = '';
    public string $priority = 'medium';
    public ?int $organization_id = null;
    public ?int $department_id = null;
    public string $start_date = '';
    public string $due_date = '';
    public string $expected_result = '';
    public string $dependencies = '';

    public string $linkType = '';
    public ?int $linkId = null;
    public array $linkOptions = [];

    public ?int $responsible_person_id = null;
    public string $leadSearch = '';
    public string $leadSelectedName = '';
    public array $leadResults = [];

    public array $supportPersonIds = [];
    public array $watcherPersonIds = [];
    public string $supportSearch = '';
    public array $supportResults = [];

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingPriorityFilter(): void { $this->resetPage(); }
    public function updatingDepartmentFilter(): void { $this->resetPage(); }
    public function updatingOverdueOnly(): void { $this->resetPage(); }

    public function updatedOrganizationId(): void
    {
        $this->department_id = null;
    }

    public function updatedLinkType(): void
    {
        $this->linkId = null;
        $this->loadLinkOptions();
    }

    private function loadLinkOptions(): void
    {
        if (!$this->linkType || !$this->organization_id) {
            $this->linkOptions = [];
            return;
        }

        $this->linkOptions = match ($this->linkType) {
            'department' => Department::where('organization_id', $this->organization_id)->orderBy('name')->get(['id', 'name'])->toArray(),
            'project' => Project::whereHas('department', fn ($q) => $q->where('organization_id', $this->organization_id))
                ->orderBy('name')->get(['id', 'name'])->toArray(),
            'workplan_activity' => WorkplanActivity::whereHas('workplan', fn ($q) => $q->where('organization_id', $this->organization_id))
                ->get(['id', 'activity'])->map(fn ($a) => ['id' => $a->id, 'name' => $a->activity])->toArray(),
            'institution' => Organization::whereIn('id', Organization::find($this->organization_id)?->subtreeIds() ?? [])
                ->where('id', '!=', $this->organization_id)->orderBy('display_name')->get(['id', 'display_name'])
                ->map(fn ($o) => ['id' => $o->id, 'name' => $o->display_name])->toArray(),
            default => [],
        };
    }

    public function create(): void
    {
        $this->authorize('create', Assignment::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->category = '';
        $this->priority = 'medium';
        $this->organization_id = null;
        $this->department_id = null;
        $this->start_date = '';
        $this->due_date = '';
        $this->expected_result = '';
        $this->dependencies = '';
        $this->linkType = '';
        $this->linkId = null;
        $this->linkOptions = [];
        $this->responsible_person_id = null;
        $this->leadSearch = '';
        $this->leadSelectedName = '';
        $this->leadResults = [];
        $this->supportPersonIds = [];
        $this->watcherPersonIds = [];
        $this->supportSearch = '';
        $this->supportResults = [];
        $this->resetValidation();
    }

    public function updatedLeadSearch(): void
    {
        if (strlen($this->leadSearch) < 2) {
            $this->leadResults = [];
            return;
        }
        $this->leadResults = $this->searchPeople($this->leadSearch);
    }

    public function updatedSupportSearch(): void
    {
        if (strlen($this->supportSearch) < 2) {
            $this->supportResults = [];
            return;
        }
        $this->supportResults = $this->searchPeople($this->supportSearch);
    }

    private function searchPeople(string $term): array
    {
        return Person::query()
            ->where(function ($q) use ($term) {
                $q->where('given_name', 'like', "%{$term}%")->orWhere('family_name', 'like', "%{$term}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name'])
            ->toArray();
    }

    public function selectLead(int $personId, string $name): void
    {
        $this->responsible_person_id = $personId;
        $this->leadSelectedName = $name;
        $this->leadSearch = $name;
        $this->leadResults = [];
    }

    public function addSupportPerson(int $personId): void
    {
        if (!in_array($personId, $this->supportPersonIds, true)) {
            $this->supportPersonIds[] = $personId;
        }
        $this->supportSearch = '';
        $this->supportResults = [];
    }

    public function removeSupportPerson(int $personId): void
    {
        $this->supportPersonIds = array_values(array_diff($this->supportPersonIds, [$personId]));
    }

    public function toggleWatcher(int $personId): void
    {
        if (in_array($personId, $this->watcherPersonIds, true)) {
            $this->watcherPersonIds = array_values(array_diff($this->watcherPersonIds, [$personId]));
        } else {
            $this->watcherPersonIds[] = $personId;
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        $assignment = $this->editingId ? Assignment::findOrFail($this->editingId) : null;

        $this->authorize($assignment ? 'update' : 'create', $assignment ?? Assignment::class);

        $allowedOrgIds = $this->organizationOptions()->pluck('id');

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'required|in:low,medium,high,urgent',
            'organization_id' => ['required', Rule::in($allowedOrgIds)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('organization_id', $this->organization_id)],
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'responsible_person_id' => 'nullable|exists:persons,id',
        ]);

        // Cross-organization assignee rejection: every named person must be
        // actively affiliated with the assignment's organization.
        $allPersonIds = array_filter(array_unique(array_merge(
            [$this->responsible_person_id],
            $this->supportPersonIds,
            $this->watcherPersonIds
        )));

        foreach ($allPersonIds as $personId) {
            $person = Person::find($personId);
            if (!$person || !$person->hasAffiliationWith($this->organization_id)) {
                $this->addError('responsible_person_id', 'One or more selected people are not affiliated with this organization.');
                return;
            }
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'category' => $this->category ?: null,
            'priority' => $this->priority,
            'organization_id' => $this->organization_id,
            'department_id' => $this->department_id,
            'start_date' => $this->start_date ?: null,
            'due_date' => $this->due_date ?: null,
            'expected_result' => $this->expected_result ?: null,
            'dependencies' => $this->dependencies ?: null,
            'responsible_person_id' => $this->responsible_person_id,
            'assignable_type' => $this->linkType && $this->linkId ? $this->assignableClassFor($this->linkType) : null,
            'assignable_id' => $this->linkType && $this->linkId ? $this->linkId : null,
            'updated_by' => $user->id,
        ];

        if ($assignment) {
            $before = Assignment::trackedFieldSnapshot($assignment);
            $assignment->update($data);
            $assignment->logFieldChanges($before);
            AuditLogger::record($assignment, 'assignment.updated', [], $assignment->organization_id);
        } else {
            $data['created_by'] = $user->id;
            $data['status'] = 'not_started';
            $assignment = Assignment::create($data);
            AuditLogger::record($assignment, 'assignment.created', [], $assignment->organization_id);
        }

        $assignment->supportPeople()->sync([]);
        $assignment->watchers()->sync([]);
        foreach ($this->supportPersonIds as $personId) {
            $assignment->supportPeople()->attach($personId, ['role' => 'support', 'created_by' => $user->id]);
        }
        foreach ($this->watcherPersonIds as $personId) {
            $assignment->watchers()->attach($personId, ['role' => 'watcher', 'created_by' => $user->id]);
        }

        $assignment->refresh();
        foreach (AssignmentNotificationTargets::forAssignment($assignment) as $recipient) {
            $recipient->notify(new AssignmentAssigned($assignment));
        }

        session()->flash('success', $this->editingId ? 'Assignment updated.' : 'Assignment created.');
        $this->closeModal();
    }

    private function assignableClassFor(string $type): string
    {
        return match ($type) {
            'department' => Department::class,
            'project' => Project::class,
            'workplan_activity' => WorkplanActivity::class,
            'institution' => Organization::class,
        };
    }

    private function organizationOptions()
    {
        $user = Auth::user();
        $directIds = $user->managedOrganizationIds();
        $viaDepartments = Department::whereIn('id', $user->managedDepartmentIds())->pluck('organization_id');

        return Organization::whereIn('id', $directIds->merge($viaDepartments)->unique())->orderBy('display_name')->get(['id', 'display_name', 'legal_name']);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-assignments'), 403);

        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        $query = Assignment::with(['department', 'organization', 'responsiblePerson'])
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->overdueOnly, fn ($q) => $q->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled']))
            ->orderByDesc('created_at');

        return view('livewire.assignments.assignments-management', [
            'assignments' => $query->paginate(15),
            'organizations' => $this->organizationOptions(),
            'departments' => Department::whereIn('id', $deptIds)->orWhereIn('organization_id', $orgIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
