<?php

namespace App\Livewire\Assignments;

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AssignmentDashboard extends Component
{
    public string $departmentFilter = '';
    public string $responsiblePersonFilter = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';
    public string $dueFrom = '';
    public string $dueTo = '';

    protected $queryString = [
        'departmentFilter' => ['except' => ''],
        'responsiblePersonFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
    ];

    private function scopedQuery()
    {
        $user = Auth::user();
        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        return Assignment::query()
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->responsiblePersonFilter, fn ($q) => $q->where('responsible_person_id', $this->responsiblePersonFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->dueFrom, fn ($q) => $q->whereDate('due_date', '>=', $this->dueFrom))
            ->when($this->dueTo, fn ($q) => $q->whereDate('due_date', '<=', $this->dueTo));
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-assignment-dashboard'), 403);

        $assignments = $this->scopedQuery()->with(['department', 'responsiblePerson'])->get();
        $overdue = $assignments->filter(fn (Assignment $a) => $a->isOverdue());

        $departmentIds = Department::whereIn('organization_id', $user->managedOrganizationIds())
            ->pluck('id')->merge($user->managedDepartmentIds())->unique();

        return view('livewire.assignments.assignment-dashboard', [
            'total' => $assignments->count(),
            'notStarted' => $assignments->where('status', 'not_started')->count(),
            'inProgress' => $assignments->where('status', 'in_progress')->count(),
            'blocked' => $assignments->where('status', 'blocked')->count(),
            'awaitingReview' => $assignments->where('status', 'awaiting_review')->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'deferred' => $assignments->where('status', 'deferred')->count(),
            'overdueCount' => $overdue->count(),
            'overdueAssignments' => $overdue->values(),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('name')->get(['id', 'name']),
            'people' => Person::whereIn('id', $assignments->pluck('responsible_person_id')->filter()->unique())->get(['id', 'given_name', 'family_name']),
        ]);
    }
}
