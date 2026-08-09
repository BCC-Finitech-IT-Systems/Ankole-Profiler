<?php

namespace App\Livewire\Policies;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyVersion;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PoliciesManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';
    public ?int $departmentFilter = null;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $reference_code = '';
    public string $policy_category = '';
    public ?int $organization_id = null;
    public ?int $department_id = null;
    public array $availableDepartments = [];

    public ?int $confirmingArchiveId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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

    public function updatedOrganizationId(): void
    {
        $this->department_id = null;
        $this->availableDepartments = $this->departmentOptionsFor($this->organization_id);
    }

    // ─── Modal open/close ────────────────────────────────────────────────────

    public function create(): void
    {
        $this->authorize('create', Policy::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $policy = Policy::findOrFail($id);
        $this->authorize('update', $policy);

        $this->editingId = $id;
        $this->title = $policy->title;
        $this->reference_code = $policy->reference_code;
        $this->policy_category = $policy->policy_category;
        $this->organization_id = $policy->organization_id;
        $this->department_id = $policy->department_id;
        $this->availableDepartments = $this->departmentOptionsFor($this->organization_id);
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
        $this->reference_code = '';
        $this->policy_category = '';
        $this->organization_id = null;
        $this->department_id = null;
        $this->availableDepartments = [];
        $this->resetValidation();
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $user = Auth::user();
        $policy = $this->editingId ? Policy::findOrFail($this->editingId) : null;

        $this->authorize($policy ? 'update' : 'create', $policy ?? Policy::class);

        $dioceseIds = $this->dioceseOptions()->pluck('id');
        $allowedDepartmentIds = collect($this->departmentOptionsFor($this->organization_id))->pluck('id');

        $this->validate([
            'title' => 'required|string|max:255',
            'reference_code' => 'required|string|max:50',
            'policy_category' => 'required|string|max:100',
            'organization_id' => ['required', Rule::in($dioceseIds)],
            'department_id' => ['nullable', Rule::in($allowedDepartmentIds)],
        ]);

        $data = [
            'title' => $this->title,
            'reference_code' => $this->reference_code,
            'policy_category' => $this->policy_category,
            'organization_id' => $this->organization_id,
            'department_id' => $this->department_id,
            'updated_by' => $user->id,
        ];

        if ($policy) {
            $policy->update($data);
            AuditLogger::record($policy, 'policy.updated', ['new' => $data], $policy->organization_id);
            session()->flash('success', 'Policy updated successfully.');
        } else {
            $data['created_by'] = $user->id;
            $data['status'] = 'draft';

            $policy = DB::transaction(function () use ($data) {
                $policy = Policy::create($data);

                $version = PolicyVersion::create([
                    'policy_id' => $policy->id,
                    'version_number' => 1,
                    'version_label' => '1.0',
                    'status' => 'draft',
                    'visibility' => 'diocese_wide',
                    'created_by' => Auth::id(),
                ]);

                $policy->update(['current_version_id' => $version->id]);

                return $policy;
            });

            AuditLogger::record($policy, 'policy.created', [], $policy->organization_id);
            session()->flash('success', 'Policy created successfully.');
        }

        $this->closeModal();
    }

    // ─── Archive ──────────────────────────────────────────────────────────────

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
        $policy = Policy::findOrFail($this->confirmingArchiveId);
        $this->authorize('archive', $policy);

        $policy->update(['status' => 'archived', 'updated_by' => Auth::id()]);
        AuditLogger::record($policy, 'policy.archived', [], $policy->organization_id);

        $this->confirmingArchiveId = null;
        session()->flash('success', 'Policy archived.');
    }

    // ─── Scoped option lists ────────────────────────────────────────────────

    /**
     * Dioceses this user may create/manage policies under: dioceses they
     * administer directly, plus dioceses reached via a department they
     * manage (Department Manager, no diocese-level affiliation).
     */
    private function dioceseOptions()
    {
        $user = Auth::user();
        $directIds = $user->managedOrganizationIds();
        $viaDepartments = Department::whereIn('id', $user->managedDepartmentIds())->pluck('organization_id');

        return Organization::whereIn('id', $directIds->merge($viaDepartments)->unique())
            ->where('category', 'diocese')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'legal_name']);
    }

    /**
     * Departments selectable under $organizationId: all departments in that
     * diocese for a diocese-level admin, or only the user's own managed
     * departments otherwise (so a Department Manager cannot pick a sibling
     * department's id by tampering with the request).
     */
    private function departmentOptionsFor(?int $organizationId): array
    {
        if (!$organizationId) {
            return [];
        }

        $user = Auth::user();
        $query = Department::where('organization_id', $organizationId)->where('is_active', true);

        if (!$user->managedOrganizationIds()->contains($organizationId)) {
            $query->whereIn('id', $user->managedDepartmentIds());
        }

        return $query->orderBy('name')->get(['id', 'name'])->toArray();
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $user = Auth::user();
        abort_unless($user->can('view-policies'), 403);

        $dioceseIds = $user->managedOrganizationIds()
            ->intersect(Organization::where('category', 'diocese')->pluck('id'));
        $departmentIds = $user->managedDepartmentIds();

        $query = Policy::with(['department', 'organization', 'currentVersion'])
            ->where(function ($q) use ($dioceseIds, $departmentIds) {
                $q->whereIn('organization_id', $dioceseIds)
                  ->orWhereIn('department_id', $departmentIds);
            })
            ->search($this->search)
            ->when($this->categoryFilter, fn ($q) => $q->where('policy_category', $this->categoryFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderByDesc('created_at');

        return view('livewire.policies.policies-management', [
            'policies' => $query->paginate(15),
            'dioceses' => $this->dioceseOptions(),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
