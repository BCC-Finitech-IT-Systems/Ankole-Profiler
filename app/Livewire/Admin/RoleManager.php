<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showPermissionsModal = false;

    public $roleId;
    public $name = '';
    public $guardName = 'web';
    public $description = '';
    public $selectedPermissions = [];

    public function mount(): void
    {
        Gate::authorize('manage-roles');
    }

    protected $rules = [
        'name' => 'required|string|max:255|unique:roles,name',
        'guardName' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'name.required' => 'Role name is required.',
        'name.unique' => 'A role with this name already exists.',
        'guardName.required' => 'Guard name is required.',
    ];

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        $permissions = Permission::orderBy('name')->get();

        return view('livewire.admin.role-manager', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function fillSampleData(): void
    {
        $this->name        = 'Content Manager';
        $this->guardName   = 'web';
        $this->description = 'Can create, edit and publish content across the portal.';
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($roleId)
    {
        $role = Role::findOrFail($roleId);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->guardName = $role->guard_name;
        $this->description = $role->description ?? '';

        $this->showEditModal = true;
    }

    public function openPermissionsModal($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        $this->roleId = $role->id;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();

        $this->showPermissionsModal = true;
    }

    public function openDeleteModal($roleId)
    {
        $this->roleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function createRole()
    {
        Gate::authorize('manage-roles');
        $this->validate();

        Role::create([
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'description' => $this->description,
        ]);

        $this->resetForm();
        $this->showCreateModal = false;

        session()->flash('message', 'Role created successfully!');
    }

    public function updateRole()
    {
        Gate::authorize('manage-roles');
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'guardName' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $role = Role::findOrFail($this->roleId);
        $role->update([
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'description' => $this->description,
        ]);

        $this->resetForm();
        $this->showEditModal = false;

        session()->flash('message', 'Role updated successfully!');
    }

    public function updatePermissions()
    {
        Gate::authorize('manage-roles');
        $role = Role::findOrFail($this->roleId);
        $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();

        $role->syncPermissions($permissions);

        $this->showPermissionsModal = false;
        $this->resetForm();

        session()->flash('message', 'Role permissions updated successfully!');
    }

    public function deleteRole()
    {
        Gate::authorize('manage-roles');
        $role = Role::findOrFail($this->roleId);

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            session()->flash('error', 'Cannot delete role. It is assigned to one or more users.');
            $this->showDeleteModal = false;
            return;
        }

        $role->delete();

        $this->showDeleteModal = false;
        session()->flash('message', 'Role deleted successfully!');
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
        $this->roleId = null;
        $this->name = '';
        $this->guardName = 'web';
        $this->description = '';
        $this->selectedPermissions = [];
        $this->resetErrorBag();
    }
}
