<?php

namespace App\Livewire\Workplans;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Person;
use App\Models\WorkplanActivity;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkplanDashboard extends Component
{
    public string $departmentFilter = '';
    public string $responsiblePersonFilter = '';
    public string $quarterFilter = '';
    public string $objectiveFilter = '';

    protected $queryString = [
        'departmentFilter' => ['except' => ''],
        'responsiblePersonFilter' => ['except' => ''],
        'quarterFilter' => ['except' => ''],
        'objectiveFilter' => ['except' => ''],
    ];

    private function scopedDepartmentIds()
    {
        $user = Auth::user();
        $orgDepartmentIds = Department::whereIn('organization_id', $user->managedOrganizationIds())->pluck('id');

        return $orgDepartmentIds->merge($user->managedDepartmentIds())->unique();
    }

    private function activityQuarter(WorkplanActivity $activity): ?int
    {
        $date = $activity->end_date ?? $activity->start_date;

        return $date ? (int) ceil($date->month / 3) : null;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-workplan-dashboard'), 403);

        $departmentIds = $this->scopedDepartmentIds();

        $activities = WorkplanActivity::query()
            ->whereHas('workplan', fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->with(['workplan.department', 'responsiblePerson'])
            ->when($this->departmentFilter, fn ($q) => $q->whereHas('workplan', fn ($wq) => $wq->where('department_id', $this->departmentFilter)))
            ->when($this->responsiblePersonFilter, fn ($q) => $q->where('responsible_person_id', $this->responsiblePersonFilter))
            ->when($this->objectiveFilter, fn ($q) => $q->where('strategic_objective', 'like', "%{$this->objectiveFilter}%"))
            ->get()
            ->when($this->quarterFilter, fn ($collection) => $collection->filter(
                fn (WorkplanActivity $activity) => $this->activityQuarter($activity) === (int) $this->quarterFilter
            ));

        $overdue = $activities->filter(fn (WorkplanActivity $a) => $a->isOverdue());

        return view('livewire.workplans.workplan-dashboard', [
            'total' => $activities->count(),
            'completed' => $activities->where('status', 'completed')->count(),
            'ongoing' => $activities->where('status', 'in_progress')->count(),
            'pending' => $activities->where('status', 'not_started')->count(),
            'deferred' => $activities->where('status', 'deferred')->count(),
            'overdueCount' => $overdue->count(),
            'overdueActivities' => $overdue->values(),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('name')->get(['id', 'name']),
            'people' => Person::whereIn('id', $activities->pluck('responsible_person_id')->filter()->unique())->get(['id', 'given_name', 'family_name']),
        ]);
    }
}
