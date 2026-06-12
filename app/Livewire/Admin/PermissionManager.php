<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class PermissionManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    public $permissionId;
    public $name = '';
    public $guardName = 'web';
    public $description = '';

    public function mount(): void
    {
        Gate::authorize('manage-permissions');
    }

    protected $rules = [
        'name' => 'required|string|max:255|unique:permissions,name',
        'guardName' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'name.required' => 'Permission name is required.',
        'name.unique' => 'A permission with this name already exists.',
        'guardName.required' => 'Guard name is required.',
    ];

    public function render()
    {
        $permissions = Permission::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.permission-manager', [
            'permissions' => $permissions,
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);

        $this->permissionId = $permission->id;
        $this->name = $permission->name;
        $this->guardName = $permission->guard_name;
        $this->description = $permission->description ?? '';

        $this->showEditModal = true;
    }

    public function openDeleteModal($permissionId)
    {
        $this->permissionId = $permissionId;
        $this->showDeleteModal = true;
    }

    public function createPermission()
    {
        Gate::authorize('manage-permissions');
        $this->validate();

        Permission::create([
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'description' => $this->description,
        ]);

        $this->resetForm();
        $this->showCreateModal = false;

        session()->flash('message', 'Permission created successfully!');
    }

    public function updatePermission()
    {
        Gate::authorize('manage-permissions');
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->permissionId,
            'guardName' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::findOrFail($this->permissionId);
        $permission->update([
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'description' => $this->description,
        ]);

        $this->resetForm();
        $this->showEditModal = false;

        session()->flash('message', 'Permission updated successfully!');
    }

    public function deletePermission()
    {
        Gate::authorize('manage-permissions');
        $permission = Permission::findOrFail($this->permissionId);

        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            session()->flash('error', 'Cannot delete permission. It is assigned to one or more roles.');
            $this->showDeleteModal = false;
            return;
        }

        $permission->delete();

        $this->showDeleteModal = false;
        session()->flash('message', 'Permission deleted successfully!');
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->permissionId = null;
        $this->name = '';
        $this->guardName = 'web';
        $this->description = '';
        $this->resetErrorBag();
    }
}
