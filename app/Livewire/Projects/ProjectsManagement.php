<?php

namespace App\Livewire\Projects;

use App\Models\Department;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectsManagement extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $statusFilter = '';
    public ?int $departmentFilter = null;

    // Modal state
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public ?int $department_id = null;
    public ?int $organization_unit_id = null;
    public ?int $client_person_id = null;
    public string $clientSearch = '';
    public string $clientSelectedName = '';
    public string $external_client_name = '';
    public bool $is_active = true;
    public string $starts_on = '';
    public string $ends_on = '';
    public array $clientResults = [];
    public array $availableUnits = [];

    // Delete confirmation
    public ?int $confirmingDeleteId = null;

    protected $queryString = [
        'search'           => ['except' => ''],
        'statusFilter'     => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
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

    // ─── Client person search ───────────────────────────────────────────────

    public function updatedClientSearch(): void
    {
        if (strlen($this->clientSearch) < 2) {
            $this->clientResults = [];
            return;
        }

        $this->clientResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->clientSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->clientSearch}%")
                  ->orWhere('person_id', 'like', "%{$this->clientSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name', 'person_id'])
            ->toArray();
    }

    public function selectClient(int $personId, string $name): void
    {
        $this->client_person_id = $personId;
        $this->clientSelectedName = $name;
        $this->clientSearch = $name;
        $this->clientResults = [];
    }

    public function clearClient(): void
    {
        $this->client_person_id = null;
        $this->clientSelectedName = '';
        $this->clientSearch = '';
        $this->clientResults = [];
    }

    // ─── Department change reloads units ─────────────────────────────────────

    public function updatedDepartmentId(): void
    {
        $this->organization_unit_id = null;
        $this->loadAvailableUnits();
    }

    private function loadAvailableUnits(): void
    {
        if (!$this->department_id) {
            $this->availableUnits = [];
            return;
        }

        $this->availableUnits = OrganizationUnit::query()
            ->where('department_id', $this->department_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    // ─── Modal open/close ────────────────────────────────────────────────────

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $project = Project::with('client')->find($id);
        if (!$project) {
            return;
        }

        $user = Auth::user();
        if (!$user->hasRole('Super Admin')) {
            abort_unless($user->managedDepartmentIds()->contains($project->department_id), 403);
        }

        $this->editingId = $id;
        $this->name = $project->name ?? '';
        $this->code = $project->code ?? '';
        $this->description = $project->description ?? '';
        $this->department_id = $project->department_id;
        $this->organization_unit_id = $project->organization_unit_id;
        $this->client_person_id = $project->client_person_id;
        $this->clientSelectedName = $project->client?->full_name ?? '';
        $this->clientSearch = $this->clientSelectedName;
        $this->external_client_name = $project->external_client_name ?? '';
        $this->is_active = (bool) $project->is_active;
        $this->starts_on = $project->starts_on?->format('Y-m-d') ?? '';
        $this->ends_on = $project->ends_on?->format('Y-m-d') ?? '';
        $this->loadAvailableUnits();
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
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->department_id = null;
        $this->organization_unit_id = null;
        $this->client_person_id = null;
        $this->clientSelectedName = '';
        $this->clientSearch = '';
        $this->external_client_name = '';
        $this->is_active = true;
        $this->starts_on = '';
        $this->ends_on = '';
        $this->clientResults = [];
        $this->availableUnits = [];
        $this->resetValidation();
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user?->can('edit-projects') || $user?->can('create-projects'), 403);

        $this->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'nullable|string|max:50',
            'description'            => 'nullable|string',
            'department_id'          => 'required|exists:departments,id',
            'organization_unit_id'   => [
                'nullable',
                Rule::exists('organization_units', 'id')
                    ->where('department_id', $this->department_id),
            ],
            'client_person_id'       => 'nullable|exists:persons,id',
            'external_client_name'   => 'nullable|string|max:255',
            'starts_on'              => 'nullable|date',
            'ends_on'                => 'nullable|date|after_or_equal:starts_on',
        ]);

        if (!$user->hasRole('Super Admin')) {
            abort_unless(
                $user->managedDepartmentIds()->contains((int) $this->department_id),
                403
            );
        }

        $data = [
            'name'                 => $this->name,
            'code'                 => $this->code ?: null,
            'description'          => $this->description ?: null,
            'department_id'        => $this->department_id,
            'organization_unit_id' => $this->organization_unit_id,
            'client_person_id'     => $this->client_person_id,
            'external_client_name' => $this->client_person_id ? null : ($this->external_client_name ?: null),
            'is_active'            => $this->is_active,
            'starts_on'            => $this->starts_on ?: null,
            'ends_on'              => $this->ends_on ?: null,
        ];

        if ($this->editingId) {
            $project = Project::find($this->editingId);
            if ($project && !$user->hasRole('Super Admin')) {
                abort_unless($user->managedDepartmentIds()->contains($project->department_id), 403);
            }
            $project?->update($data);
            session()->flash('success', 'Project updated successfully.');
        } else {
            Project::create($data);
            session()->flash('success', 'Project created successfully.');
        }

        $this->closeModal();
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $user = Auth::user();
        abort_unless($user?->can('delete-projects'), 403);

        $project = Project::find($this->confirmingDeleteId);
        if (!$project) {
            $this->confirmingDeleteId = null;
            return;
        }

        if (!$user->hasRole('Super Admin')) {
            abort_unless($user->managedDepartmentIds()->contains($project->department_id), 403);
        }

        $project->delete();
        $this->confirmingDeleteId = null;
        session()->flash('success', 'Project deleted.');
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $user = Auth::user();
        abort_unless($user?->can('view-projects'), 403);

        $allowedDeptIds = $user->hasRole('Super Admin')
            ? null
            : $user->managedDepartmentIds();

        $query = Project::with(['department.organization'])
            ->when($allowedDeptIds !== null, fn($q) => $q->whereIn('department_id', $allowedDeptIds))
            ->when($this->search, fn($q) => $q->where(function ($inner) {
                $inner->where('name', 'like', "%{$this->search}%")
                      ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->orderBy('name');

        $departments = $allowedDeptIds !== null
            ? Department::whereIn('id', $allowedDeptIds)->orderBy('name')->get(['id', 'name'])
            : Department::orderBy('name')->get(['id', 'name']);

        return view('livewire.projects.projects-management', [
            'projects'    => $query->paginate(15),
            'departments' => $departments,
        ]);
    }
}
