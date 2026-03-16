<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RoleType;
use App\Models\Organization;
use App\Models\Department;
use App\Models\PersonAffiliation;
use Spatie\Permission\Models\Permission;

class RoleTypeManager extends Component
{
    use WithPagination;

    public $search = '';
    public $activeFilter = 'all';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showPermissionsModal = false;

    public $roleTypeId;
    public $department_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $active = true;
    public $selectedPermissions = [];

    protected $rules = [
        'department_id' => 'required|exists:departments,id',
        'code' => 'required|string|max:50|unique:role_types,code',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'active' => 'boolean',
    ];

    protected $messages = [
        'code.required' => 'Occupation code is required.',
        'code.unique' => 'An occupation with this code already exists.',
        'name.required' => 'Occupation name is required.',
        'department_id.required' => 'Department/Project is required.',
        'department_id.exists' => 'Selected department does not exist.',
    ];

    /**
     * Get the current user's active affiliation
     */
    private function getUserAffiliation()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return PersonAffiliation::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get departments available to the current user based on their role/affiliation
     */
    private function getUserDepartments()
    {
        $user = Auth::user();

        if (!$user) {
            return collect();
        }

        // Super Admin can see all departments
        if ($user->hasRole('Super Admin')) {
            return Department::with('organization')
                ->orderBy('name')
                ->get();
        }

        // Get user's active affiliation
        $affiliation = $this->getUserAffiliation();

        if (!$affiliation) {
            return collect();
        }

        if ($user->hasRole('Organization Admin')) {
            // Organization Admin can see all departments in their organization
            return Department::with('organization')
                ->where('organization_id', $affiliation->organization_id)
                ->orderBy('name')
                ->get();
        }

        if ($user->hasRole('Project Admin')) {
            // Project Admin can only see their specific department
            if ($affiliation->department_id) {
                return Department::with('organization')
                    ->where('id', $affiliation->department_id)
                    ->get();
            }
        }

        // Regular users - show their department only
        if ($affiliation->department_id) {
            return Department::with('organization')
                ->where('id', $affiliation->department_id)
                ->get();
        }

        return collect();
    }

    /**
     * Check if user can manage role types
     */
    private function canManageRoleTypes(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->hasRole('Super Admin') ||
            $user->hasRole('Organization Admin') ||
            $user->hasRole('Project Admin')
        );
    }

    /**
     * Check if user can manage a specific role type
     */
    private function canManageRoleType(RoleType $roleType): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $affiliation = $this->getUserAffiliation();
        if (!$affiliation) return false;

        if ($user->hasRole('Organization Admin')) {
            // Can manage role types in their organization's departments
            return $roleType->department?->organization_id === $affiliation->organization_id;
        }

        if ($user->hasRole('Project Admin')) {
            // Can only manage role types in their department
            return $roleType->department_id === $affiliation->department_id;
        }

        return false;
    }

    public function render()
    {
        $user = Auth::user();
        $query = RoleType::with(['department.organization', 'permissions']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Active filter
        if ($this->activeFilter === 'active') {
            $query->where('active', true);
        } elseif ($this->activeFilter === 'inactive') {
            $query->where('active', false);
        }

        // Scope based on user role
        if ($user) {
            if ($user->hasRole('Super Admin')) {
                // No additional filtering - can see all
            } else {
                $affiliation = $this->getUserAffiliation();
                if ($affiliation) {
                    if ($user->hasRole('Organization Admin')) {
                        // Filter by departments in their organization
                        $query->whereHas('department', function ($q) use ($affiliation) {
                            $q->where('organization_id', $affiliation->organization_id);
                        });
                    } elseif ($user->hasRole('Project Admin')) {
                        // Filter by their specific department
                        $query->where('department_id', $affiliation->department_id);
                    } else {
                        // Regular user - their department only
                        $query->where('department_id', $affiliation->department_id);
                    }
                } else {
                    // No affiliation, show nothing
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $roleTypes = $query->orderBy('name')->paginate(15);
        $permissions = Permission::orderBy('name')->get();
        $departments = $this->getUserDepartments();

        return view('livewire.admin.role-type-manager', [
            'roleTypes' => $roleTypes,
            'permissions' => $permissions,
            'departments' => $departments,
            'canManage' => $this->canManageRoleTypes(),
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!$this->canManageRoleTypes()) {
            session()->flash('error', 'You do not have permission to create occupations.');
            return;
        }

        $this->resetForm();

        $departments = $this->getUserDepartments();

        if ($departments->isEmpty()) {
            session()->flash('error', 'No departments found. You need an active affiliation with a department to create occupations.');
            return;
        }

        // Auto-select department if user only has access to one
        if ($departments->count() === 1) {
            $this->department_id = $departments->first()->id;
        }

        $this->showCreateModal = true;
    }

    public function openEditModal($roleTypeId)
    {
        $roleType = RoleType::findOrFail($roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to edit this occupation.');
            return;
        }

        $this->roleTypeId = $roleType->id;
        $this->department_id = $roleType->department_id;
        $this->code = $roleType->code;
        $this->name = $roleType->name;
        $this->description = $roleType->description ?? '';
        $this->active = $roleType->active;

        $this->showEditModal = true;
    }

    public function openPermissionsModal($roleTypeId)
    {
        $roleType = RoleType::with('permissions')->findOrFail($roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to manage this occupation\'s permissions.');
            return;
        }

        $this->roleTypeId = $roleType->id;
        $this->selectedPermissions = $roleType->permissions->pluck('id')->toArray();

        $this->showPermissionsModal = true;
    }

    public function openDeleteModal($roleTypeId)
    {
        $roleType = RoleType::findOrFail($roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to delete this occupation.');
            return;
        }

        $this->roleTypeId = $roleTypeId;
        $this->showDeleteModal = true;
    }

    public function createRoleType()
    {
        if (!$this->canManageRoleTypes()) {
            session()->flash('error', 'You do not have permission to create occupations.');
            return;
        }

        // Auto-select if only one department available
        $departments = $this->getUserDepartments();
        if ($departments->count() === 1 && empty($this->department_id)) {
            $this->department_id = $departments->first()->id;
        }

        $this->validate();

        // Verify user can create role type in this department
        $departmentIds = $departments->pluck('id')->toArray();
        if (!in_array($this->department_id, $departmentIds)) {
            session()->flash('error', 'You cannot create occupations in this department.');
            return;
        }

        RoleType::create([
            'department_id' => $this->department_id,
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
        ]);

        $this->resetForm();
        $this->showCreateModal = false;

        session()->flash('message', 'Occupation created successfully!');
    }

    public function updateRoleType()
    {
        $roleType = RoleType::findOrFail($this->roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to update this occupation.');
            return;
        }

        $this->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => 'required|string|max:50|unique:role_types,code,' . $this->roleTypeId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active' => 'boolean',
        ]);

        // Verify user can assign to this department
        $departmentIds = $this->getUserDepartments()->pluck('id')->toArray();
        if (!in_array($this->department_id, $departmentIds)) {
            session()->flash('error', 'You cannot assign occupations to this department.');
            return;
        }

        $roleType->update([
            'department_id' => $this->department_id,
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
        ]);

        $this->resetForm();
        $this->showEditModal = false;

        session()->flash('message', 'Occupation updated successfully!');
    }

    public function updatePermissions()
    {
        $roleType = RoleType::findOrFail($this->roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to manage this occupation\'s permissions.');
            return;
        }

        $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
        $roleType->syncPermissions($permissions);

        $this->showPermissionsModal = false;
        $this->resetForm();

        session()->flash('message', 'Occupation permissions updated successfully!');
    }

    public function toggleStatus($roleTypeId)
    {
        $roleType = RoleType::findOrFail($roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to change this occupation\'s status.');
            return;
        }

        $roleType->update(['active' => !$roleType->active]);

        $status = $roleType->active ? 'activated' : 'deactivated';
        session()->flash('message', "Occupation {$status} successfully!");
    }

    public function deleteRoleType()
    {
        $roleType = RoleType::findOrFail($this->roleTypeId);

        if (!$this->canManageRoleType($roleType)) {
            session()->flash('error', 'You do not have permission to delete this occupation.');
            return;
        }

        // Check if role type has any active affiliations
        if (method_exists($roleType, 'hasActiveAffiliations') && $roleType->hasActiveAffiliations()) {
            session()->flash('error', 'Cannot delete occupation. It has active affiliations.');
            $this->showDeleteModal = false;
            return;
        }

        $roleType->delete();

        $this->showDeleteModal = false;
        session()->flash('message', 'Occupation deleted successfully!');
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showPermissionsModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->roleTypeId = null;
        $this->department_id = '';
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->active = true;
        $this->selectedPermissions = [];
        $this->resetErrorBag();
    }
}
