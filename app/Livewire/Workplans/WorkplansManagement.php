<?php

namespace App\Livewire\Workplans;

use App\Models\Department;
use App\Models\Workplan;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WorkplansManagement extends Component
{
    use WithPagination;

    public string $yearFilter = '';
    public string $statusFilter = '';
    public ?int $departmentFilter = null;

    public bool $showModal = false;

    public ?int $department_id = null;
    public string $year = '';
    public string $title = '';

    public ?int $confirmingArchiveId = null;

    protected $queryString = [
        'yearFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatingYearFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Workplan::class);
        $this->resetForm();
        $this->year = (string) now()->year;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->department_id = null;
        $this->year = '';
        $this->title = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $user = Auth::user();
        $this->authorize('create', Workplan::class);

        $allowedDepartmentIds = $this->departmentOptions()->pluck('id');

        $this->validate([
            'department_id' => ['required', Rule::in($allowedDepartmentIds)],
            'year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:255',
        ]);

        $department = Department::findOrFail($this->department_id);

        $existingVersions = Workplan::where('department_id', $this->department_id)
            ->where('year', $this->year)
            ->count();

        abort_if($existingVersions > 0, 422, 'A workplan for this department and year already exists. Open it to create a revision instead.');

        $workplan = Workplan::create([
            'department_id' => $this->department_id,
            'organization_id' => $department->organization_id,
            'year' => $this->year,
            'version_number' => 1,
            'title' => $this->title ?: ('FY' . $this->year . ' Workplan'),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        AuditLogger::record($workplan, 'workplan.created', [], $workplan->organization_id);

        session()->flash('success', 'Workplan created successfully.');
        $this->closeModal();
    }

    public function confirmArchive(int $id): void
    {
        $this->confirmingArchiveId = $id;
    }

    public function cancelArchive(): void
    {
        $this->confirmingArchiveId = null;
    }

    public function archive(): void
    {
        $workplan = Workplan::findOrFail($this->confirmingArchiveId);
        $this->authorize('archive', $workplan);

        $workplan->update(['status' => 'cancelled', 'updated_by' => Auth::id()]);
        AuditLogger::record($workplan, 'workplan.cancelled', [], $workplan->organization_id);

        $this->confirmingArchiveId = null;
        session()->flash('success', 'Workplan cancelled.');
    }

    /**
     * Departments this user may create/manage workplans under: all
     * departments in an organization they administer directly, plus
     * departments they manage individually (Department Manager).
     */
    private function departmentOptions()
    {
        $user = Auth::user();
        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        return Department::where(function ($q) use ($orgIds, $deptIds) {
            $q->whereIn('organization_id', $orgIds)->orWhereIn('id', $deptIds);
        })->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-workplans'), 403);

        $orgIds = $user->managedOrganizationIds();
        $deptIds = $user->managedDepartmentIds();

        $query = Workplan::with(['department', 'organization'])
            ->where(function ($q) use ($orgIds, $deptIds) {
                $q->whereIn('organization_id', $orgIds)->orWhereIn('department_id', $deptIds);
            })
            ->when($this->yearFilter, fn ($q) => $q->where('year', $this->yearFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderByDesc('year')
            ->orderByDesc('version_number');

        return view('livewire.workplans.workplans-management', [
            'workplans' => $query->paginate(15),
            'departments' => $this->departmentOptions(),
        ]);
    }
}
