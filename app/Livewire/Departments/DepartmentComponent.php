<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Models\Project;
use App\Models\ProjectDepartment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentComponent extends Component
{

    public $selectedOrganizationPersons = [];

    public function showOrganizationPersons($organizationId)
    {
        $persons = [];
        $projects = \App\Models\Project::whereHas('department.organization', function ($q) use ($organizationId) {
            $q->where('id', $organizationId);
        })->get();
        foreach ($projects as $project) {
            foreach ($project->persons as $person) {
                $persons[] = $person;
            }
        }
        $this->selectedOrganizationPersons = collect($persons)->unique('id')->values()->all();
    }
    public $projectDeptSearch = '';
    use WithPagination;

    public $search = '';
    public $organizationFilter = '';
    public $includeInactive = false;
    public $showCreateModal = false;
    public $showEditModal = false;
    public $confirmDeleteDepartmentId = null;

    public $matchingOrgsDepartmentId = null;
    public $editingDepartmentId = null;
    public $createForm = [
        'organization_id' => '',
        'name' => '',
        'sub_categories_input' => '',
        'code' => '',
        'description' => '',
        'admin_user_id' => '',
        'is_active' => true,
    ];

    public $editForm = [
        'organization_id' => '',
        'name' => '',
        'sub_categories_input' => '',
        'code' => '',
        'description' => '',
        'admin_user_id' => '',
        'is_active' => true,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'organizationFilter' => ['except' => ''],
        'includeInactive' => ['except' => false],
    ];

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($this->hasPermissionOrSuperAdmin($user, 'view-departments'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingOrganizationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingIncludeInactive(): void
    {
        $this->resetPage();
    }

    public function fillSampleData(): void
    {
        $org = Organization::query()->first();
        $admin = User::query()->first();
        $this->createForm = [
            'organization_id'      => $org?->id ?? '',
            'name'                 => 'Education & Schools',
            'code'                 => 'EDU-' . rand(10, 99),
            'sub_categories_input' => 'Primary, Secondary, Tertiary',
            'description'          => 'Oversees all educational institutions and academic programmes within the diocese.',
            'admin_user_id'        => $admin?->id ?? '',
            'is_active'            => true,
        ];
        $this->showCreateModal = true;
    }

    public function openCreateModal(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($this->hasPermissionOrSuperAdmin($user, 'create-departments'), 403);

        $this->resetValidation();
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetValidation();
        $this->resetCreateForm();
    }

    public function createDepartment(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($this->hasPermissionOrSuperAdmin($user, 'create-departments'), 403);

        $validated = $this->validate($this->createRules());

        if (!$user->hasRole('Super Admin')) {
            abort_unless(
                in_array((int) $validated['createForm']['organization_id'], $this->allowedOrganizationIds($user), true),
                403,
                'You cannot create departments for this organization.'
            );
        }

        $department = Department::create([
            'organization_id' => (int) $validated['createForm']['organization_id'],
            'name' => trim($validated['createForm']['name']),
            'code' => $validated['createForm']['code'] !== '' ? trim($validated['createForm']['code']) : null,
            'description' => $validated['createForm']['description'] !== '' ? trim($validated['createForm']['description']) : null,
            'admin_user_id' => $validated['createForm']['admin_user_id'] !== '' ? (int) $validated['createForm']['admin_user_id'] : null,
            'is_active' => (bool) $validated['createForm']['is_active'],
        ]);

        $this->syncDepartmentSubCategories($department, (string) $validated['createForm']['sub_categories_input']);

        $this->closeCreateModal();
        session()->flash('message', 'Department created successfully.');
    }

    public function openEditModal(int $departmentId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $department = Department::query()->with('subCategories')->findOrFail($departmentId);

        abort_unless($this->canManageDepartment($user, $department), 403);

        $this->editingDepartmentId = $department->id;
        $this->editForm = [
            'organization_id' => (string) $department->organization_id,
            'name' => $department->name,
            'sub_categories_input' => $department->subCategories->pluck('name')->implode(', '),
            'code' => $department->code ?? '',
            'description' => $department->description ?? '',
            'admin_user_id' => $department->admin_user_id ? (string) $department->admin_user_id : '',
            'is_active' => (bool) $department->is_active,
        ];

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingDepartmentId = null;
        $this->resetValidation();
        $this->resetEditForm();
    }

    public function updateDepartment(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $department = Department::query()->findOrFail((int) $this->editingDepartmentId);

        abort_unless($this->canManageDepartment($user, $department), 403);

        $validated = $this->validate($this->editRules($department->id));

        if (!$user->hasRole('Super Admin')) {
            abort_unless(
                in_array((int) $validated['editForm']['organization_id'], $this->allowedOrganizationIds($user), true),
                403,
                'You cannot move this department to that organization.'
            );
        }

        $department->update([
            'organization_id' => (int) $validated['editForm']['organization_id'],
            'name' => trim($validated['editForm']['name']),
            'code' => $validated['editForm']['code'] !== '' ? trim($validated['editForm']['code']) : null,
            'description' => $validated['editForm']['description'] !== '' ? trim($validated['editForm']['description']) : null,
            'admin_user_id' => $validated['editForm']['admin_user_id'] !== '' ? (int) $validated['editForm']['admin_user_id'] : null,
            'is_active' => (bool) $validated['editForm']['is_active'],
        ]);

        $this->syncDepartmentSubCategories($department, (string) $validated['editForm']['sub_categories_input']);

        $this->closeEditModal();
        session()->flash('message', 'Department updated successfully.');
    }

    public function confirmDeleteDepartment(int $departmentId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $department = Department::query()->findOrFail($departmentId);

        abort_unless($this->canDeleteDepartment($user, $department), 403);

        $this->confirmDeleteDepartmentId = $departmentId;
    }

    public function cancelDeleteDepartment(): void
    {
        $this->confirmDeleteDepartmentId = null;
    }

    /**
     * Institutions whose category matches one of the department's
     * sub-categories, restricted to the department's own organization tree —
     * category names alone would match institutions under unrelated dioceses.
     * Returns null when the department has no sub-categories to match on.
     */
    private function matchingInstitutionsQuery(Department $department)
    {
        $subCategoryNames = $department->subCategories->pluck('name');

        if ($subCategoryNames->isEmpty() || !$department->organization) {
            return null;
        }

        return Organization::query()
            ->institutions()
            ->matchingSubCategories($subCategoryNames)
            ->whereIn('id', $department->organization->subtreeIds())
            ->orderBy('category')
            ->orderBy('legal_name');
    }

    public function showMatchingOrganizations(int $departmentId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $department = Department::query()->findOrFail($departmentId);

        // Reading which institutions a department reaches is a view of that
        // department, so it needs the same scope check the rest of the page
        // applies — not just "is signed in".
        abort_unless(
            $user->hasRole('Super Admin')
                || in_array($department->organization_id, $this->allowedOrganizationIds($user), true)
                || (int) $department->admin_user_id === (int) $user->id,
            403
        );

        $this->matchingOrgsDepartmentId = $departmentId;
        $this->projectDeptSearch = '';
    }

    public function closeMatchingOrganizations(): void
    {
        $this->matchingOrgsDepartmentId = null;
        $this->projectDeptSearch = '';
    }

    public function deleteDepartment(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $department = Department::query()->findOrFail((int) $this->confirmDeleteDepartmentId);

        abort_unless($this->canDeleteDepartment($user, $department), 403);

        // Department soft-deletes, but this used to call forceDelete(), which
        // skips that and hits the database cascades instead: projects,
        // workplans and sub-categories are all ON DELETE CASCADE, so a hard
        // delete silently took them with it and nothing could be recovered.
        // Refuse while projects are attached, and otherwise soft-delete so
        // the row can be restored.
        if ($department->projects()->exists()) {
            $this->confirmDeleteDepartmentId = null;
            session()->flash('error', 'This department still has projects attached. Move or delete them first.');

            return;
        }

        $department->delete();
        $this->confirmDeleteDepartmentId = null;

        session()->flash('message', 'Department deleted successfully.');
    }

    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 403);

        $query = Department::query()->with(['organization', 'admin', 'subCategories']);

        if ($this->organizationFilter !== '') {
            $query->where('organization_id', (int) $this->organizationFilter);
        }

        if (!$this->includeInactive) {
            $query->where('is_active', true);
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($departmentQuery) use ($search) {
                $departmentQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('organization', function ($organizationQuery) use ($search) {
                        $organizationQuery->where('legal_name', 'like', "%{$search}%");
                    });
            });
        }

        if (!$user->hasRole('Super Admin')) {
            $query->where(function ($departmentQuery) use ($user) {
                $departmentQuery
                    ->whereIn('organization_id', $this->allowedOrganizationIds($user))
                    ->orWhere('admin_user_id', $user->id);
            });
        }

        $allowedOrganizationIds = $this->allowedOrganizationIds($user);

        $organizations = Organization::query()
            ->when(!$user->hasRole('Super Admin'), function ($organizationQuery) use ($allowedOrganizationIds) {
                $organizationQuery->whereIn('id', $allowedOrganizationIds);
            })
            ->orderBy('legal_name')
            ->get(['id', 'legal_name', 'category', 'is_super']);

        // 247 flat options is unusable; split the diocese from the
        // institutions under it so the list reads as a hierarchy.
        $organizationGroups = $organizations->groupBy(
            fn (Organization $organization) => $organization->is_super
                || strtolower(trim((string) $organization->category)) === 'diocese'
                    ? 'Diocese'
                    : 'Institutions'
        );

        $admins = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        // For Organization Admin: load affiliated departments and matching organizations
        $isOrgAdmin = $user->hasRole('Organization Admin') && !$user->hasRole('Super Admin');
        $orgAdminProjects = collect();
        $orgAdminDepartments = collect();
        $orgAdminOrganizations = collect(); // organizations grouped by sub-category
        $matchingOrgsDepartment = null;

        if ($isOrgAdmin) {
            $affiliatedDepartmentIds = $user->person
                ? PersonAffiliation::where('person_id', $user->person->id)
                    ->where('status', 'active')
                    ->whereNotNull('department_id')
                    ->pluck('department_id')
                    ->unique()
                : collect();

            // An Organization Admin administers an organization; they are not
            // necessarily a member of one of its departments, and admin
            // accounts often have no Person record at all. Falling back to the
            // departments of the organizations they administer keeps the page
            // useful for them instead of showing an empty state they have no
            // way to escape.
            $orgAdminDepartments = ($affiliatedDepartmentIds->isNotEmpty()
                    ? Department::whereIn('id', $affiliatedDepartmentIds)
                    : Department::whereIn('organization_id', $this->allowedOrganizationIds($user)))
                ->with(['organization', 'admin', 'subCategories'])
                ->withCount('projects')
                ->get();

            // Per-row rather than the blunt permission flags used by the
            // Super Admin table: an Organization Admin holds edit/delete
            // permission generally but only over their own organizations, so
            // a permission-only check would render buttons that 403 on click.
            $orgAdminDepartments->each(function (Department $department) use ($user) {
                $department->can_edit = $this->canManageDepartment($user, $department);
                $department->can_delete = $this->canDeleteDepartment($user, $department);
            });

            // A count per row, so the page says how many institutions each
            // department reaches without listing hundreds of them inline.
            // The full list moved into a modal opened from that count.
            $orgAdminDepartments->each(function (Department $department) {
                $department->matching_institutions_count = $this
                    ->matchingInstitutionsQuery($department)
                    ?->count() ?? 0;
            });

            if ($this->matchingOrgsDepartmentId) {
                $selected = $orgAdminDepartments
                    ->firstWhere('id', (int) $this->matchingOrgsDepartmentId);

                $query = $selected ? $this->matchingInstitutionsQuery($selected) : null;

                if ($query) {
                    if ($this->projectDeptSearch !== '') {
                        $searchTerm = strtolower(trim($this->projectDeptSearch));
                        $query->where(function ($q) use ($searchTerm) {
                            $q->whereRaw('LOWER(legal_name) LIKE ?', ["%{$searchTerm}%"])
                              ->orWhereRaw('LOWER(code) LIKE ?', ["%{$searchTerm}%"])
                              ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchTerm}%"])
                              ->orWhereRaw('LOWER(contact_email) LIKE ?', ["%{$searchTerm}%"]);
                        });
                    }

                    $orgAdminOrganizations = $query->get()->groupBy('category');
                }

                $matchingOrgsDepartment = $selected;
            }
        }

        return view('livewire.departments.department-component', [
            'departments' => $query->latest()->paginate(20),
            'organizations' => $organizations,
            'organizationGroups' => $organizationGroups,
            'admins' => $admins,
            'canCreateDepartments' => $this->hasPermissionOrSuperAdmin($user, 'create-departments'),
            'canEditDepartments' => $this->hasPermissionOrSuperAdmin($user, 'edit-departments'),
            'canDeleteDepartments' => $this->hasPermissionOrSuperAdmin($user, 'delete-departments'),
            'isOrgAdmin' => $isOrgAdmin,
            'departmentBeingDeleted' => $this->confirmDeleteDepartmentId
                ? Department::withCount('projects')->find((int) $this->confirmDeleteDepartmentId)
                : null,
            'orgAdminDepartments' => $orgAdminDepartments,
            'matchingOrgsDepartment' => $matchingOrgsDepartment,
            'orgAdminProjects' => $orgAdminProjects,
            'orgAdminOrganizations' => $orgAdminOrganizations,
        ]);
    }

    private function createRules(): array
    {
        return [
            'createForm.organization_id' => ['required', 'exists:organizations,id'],
            'createForm.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->where(function ($query) {
                    return $query->where('organization_id', $this->createForm['organization_id']);
                }),
            ],
            'createForm.sub_categories_input' => ['nullable', 'string', 'max:1000'],
            'createForm.code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('departments', 'code')->where(function ($query) {
                    return $query
                        ->where('organization_id', $this->createForm['organization_id'])
                        ->whereNotNull('code');
                }),
            ],
            'createForm.description' => ['nullable', 'string'],
            'createForm.admin_user_id' => ['nullable', 'exists:users,id'],
            'createForm.is_active' => ['nullable', 'boolean'],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->createForm = [
            'organization_id' => '',
            'name' => '',
            'sub_categories_input' => '',
            'code' => '',
            'description' => '',
            'admin_user_id' => '',
            'is_active' => true,
        ];
    }

    private function editRules(int $departmentId): array
    {
        return [
            'editForm.organization_id' => ['required', 'exists:organizations,id'],
            'editForm.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(function ($query) {
                        return $query->where('organization_id', $this->editForm['organization_id']);
                    })
                    ->ignore($departmentId),
            ],
            'editForm.sub_categories_input' => ['nullable', 'string', 'max:1000'],
            'editForm.code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('departments', 'code')
                    ->where(function ($query) {
                        return $query
                            ->where('organization_id', $this->editForm['organization_id'])
                            ->whereNotNull('code');
                    })
                    ->ignore($departmentId),
            ],
            'editForm.description' => ['nullable', 'string'],
            'editForm.admin_user_id' => ['nullable', 'exists:users,id'],
            'editForm.is_active' => ['nullable', 'boolean'],
        ];
    }

    private function resetEditForm(): void
    {
        $this->editForm = [
            'organization_id' => '',
            'name' => '',
            'sub_categories_input' => '',
            'code' => '',
            'description' => '',
            'admin_user_id' => '',
            'is_active' => true,
        ];
    }

    private function syncDepartmentSubCategories(Department $department, string $input): void
    {
        $names = collect(explode(',', $input))
            ->map(fn(string $name) => trim($name))
            ->filter(fn(string $name) => $name !== '')
            ->unique()
            ->values();

        $department->subCategories()->delete();

        if ($names->isNotEmpty()) {
            $department->subCategories()->createMany(
                $names->map(fn(string $name) => ['name' => $name, 'is_active' => true])->all()
            );
        }
    }

    private function hasPermissionOrSuperAdmin($user, string $permission): bool
    {
        return (bool) $user && ($user->hasRole('Super Admin') || $user->can($permission));
    }

    private function allowedOrganizationIds($user): array
    {
        if ($user->hasRole('Super Admin')) {
            return Organization::query()->pluck('id')->all();
        }

        if (!$user->person) {
            return [];
        }

        $directIds = $user->person
            ->affiliations()
            ->where('status', 'active')
            ->pluck('organization_id');

        // Tenancy flows down from the diocese (see User::organizationSubtreeIds):
        // an Organization Admin affiliated with a diocese administers every
        // institution under it. Returning only the affiliated row left the
        // organization dropdown showing the diocese alone, with no way to put
        // a department on an institution. Other roles stay on their direct
        // affiliations.
        if (!$user->hasRole('Organization Admin')) {
            return $directIds->all();
        }

        return Organization::query()
            ->whereIn('id', $directIds)
            ->get()
            ->flatMap(fn (Organization $organization) => $organization->subtreeIds())
            ->unique()
            ->values()
            ->all();
    }

    private function canManageDepartment($user, Department $department): bool
    {
        if (!$this->hasPermissionOrSuperAdmin($user, 'edit-departments')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return in_array($department->organization_id, $this->allowedOrganizationIds($user), true)
            || (int) $department->admin_user_id === (int) $user->id;
    }

    private function canDeleteDepartment($user, Department $department): bool
    {
        if (!$this->hasPermissionOrSuperAdmin($user, 'delete-departments')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return in_array($department->organization_id, $this->allowedOrganizationIds($user), true)
            || (int) $department->admin_user_id === (int) $user->id;
    }
}
