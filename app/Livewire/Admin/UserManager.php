<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonAffiliation;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserManager extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = 'all';
    public $organizationFilter = 'all';
    public $showRolesModal = false;
    public $showDeleteModal = false;
    public $showOrganizationModal = false;

    public $userId;
    public $selectedRoles = [];
    public $selectedOrganization = null;
    public $selectedUsers = []; // Array to store selected user IDs
    public $selectAll = false; // Flag to handle select all functionality
    public $activeTab = 'users'; // Default active tab

    public function render()
    {
        $query = User::with(['roles', 'personAffiliation.Organization']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter !== 'all') {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->roleFilter);
            });
        }

        if ($this->organizationFilter !== 'all') {
            $query->whereHas('personAffiliation', function($q) {
                $q->where('organization_id', $this->organizationFilter);
            });
        }

        $users = $query->orderBy('name')->paginate(15);
        $roles = Role::orderBy('name')->get();
        $allRoles = Role::orderBy('name')->get();
        // Show all organizations in dropdown for assignment
        $organizations = Organization::orderBy('legal_name')->get();
        $organizationsWithContacts = Organization::select('legal_name', 'contact_email', 'contact_phone')->get();


        return view('livewire.admin.user-manager', [
            'users' => $users,
            'roles' => $roles,
            'allRoles' => $allRoles,
            'organizations' => $organizations,
            'organizationsWithContacts' => $organizationsWithContacts,
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function updatedOrganizationFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = User::where(function($query) {
                if ($this->search) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                }

                if ($this->roleFilter !== 'all') {
                    $query->whereHas('roles', function($q) {
                        $q->where('name', $this->roleFilter);
                    });
                }

                if ($this->organizationFilter !== 'all') {
                    $query->where('organization_id', $this->organizationFilter);
                }
            })->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function openRolesModal($userId)
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->userId = $user->id;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();

        $this->showRolesModal = true;
    }

    public function updateUserRoles()
    {
        $user = User::findOrFail($this->userId);
        $roles = Role::whereIn('id', $this->selectedRoles)->get();

        $user->syncRoles($roles);

        $this->showRolesModal = false;
        $this->resetForm();

        session()->flash('message', 'User roles updated successfully!');
    }

    public function openOrganizationModal($userId)
    {
        $user = User::with(['person.affiliations'])->findOrFail($userId);

        $this->userId = $user->id;
        
        // Get organization from PersonAffiliation if user has a person record
        if ($user->person && $user->person->affiliations->count() > 0) {
            $activeAffiliation = $user->person->affiliations->where('status', 'active')->first();
            $this->selectedOrganization = $activeAffiliation ? $activeAffiliation->organization_id : null;
        } else {
            $this->selectedOrganization = null;
        }

        $this->showOrganizationModal = true;
    }

    public function updateUserOrganization()
    {
        $user = User::findOrFail($this->userId);

        // If user doesn't have a person record, create one
        if (!$user->person) {
            $nameParts = explode(' ', $user->name, 2);
            $person = Person::create([
                'person_id' => Person::generatePersonId(),
                'global_identifier' => Str::uuid(),
                'user_id' => $user->id,
                'given_name' => $nameParts[0] ?? $user->name,
                'family_name' => $nameParts[1] ?? '',
                'status' => 'active',
            ]);
        } else {
            $person = $user->person;
        }

        // If an organization is selected, create or update PersonAffiliation
        if ($this->selectedOrganization) {
            // Generate unique affiliation ID
            $lastAffiliation = PersonAffiliation::where('affiliation_id', 'like', 'AFF-%')
                ->orderByRaw("CAST(SUBSTRING(affiliation_id, 5) AS UNSIGNED) DESC")
                ->first();
            
            if ($lastAffiliation) {
                $lastNumber = (int) substr($lastAffiliation->affiliation_id, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $affiliationId = 'AFF-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            
            PersonAffiliation::updateOrCreate(
                [
                    'person_id' => $person->id,
                    'organization_id' => $this->selectedOrganization,
                ],
                [
                    'affiliation_id' => $affiliationId,
                    'role_type' => 'MEMBER', // Default role type for user-assigned organization
                    'start_date' => now()->toDateString(),
                    'status' => 'active',
                ]
            );
        } else {
            // If no organization selected, deactivate existing affiliation
            PersonAffiliation::where('person_id', $person->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'end_date' => now()->toDateString()]);
        }

        $this->showOrganizationModal = false;
        $this->resetForm();

        // Clear pagination to reset to first page and refresh data
        $this->resetPage();

        session()->flash('message', 'User organization updated successfully!');
    }

    public function openDeleteModal($userId)
    {
        $this->userId = $userId;
        $this->showDeleteModal = true;
    }

    public function deleteUser()
    {
        $user = User::findOrFail($this->userId);

        // Check if user has any critical data that should prevent deletion
        // Add any business logic checks here

        $user->delete();

        $this->showDeleteModal = false;
        session()->flash('message', 'User deleted successfully!');
    }

    public function bulkDelete()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'No users selected for deletion.');
            return;
        }

        User::whereIn('id', $this->selectedUsers)->delete();

        $this->selectedUsers = [];
        $this->selectAll = false;

        session()->flash('message', 'Selected users deleted successfully!');
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);

        // Toggle email_verified_at to simulate active/inactive status
        // You might want to add a proper 'active' field to your users table
        if ($user->email_verified_at) {
            $user->update(['email_verified_at' => null]);
            session()->flash('message', 'User deactivated successfully!');
        } else {
            $user->update(['email_verified_at' => now()]);
            session()->flash('message', 'User activated successfully!');
        }
    }

    public function closeModals()
    {
        $this->showRolesModal = false;
        $this->showDeleteModal = false;
        $this->showOrganizationModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->userId = null;
        $this->selectedRoles = [];
        $this->selectedOrganization = null;
        $this->selectedUsers = [];
        $this->selectAll = false;
        $this->resetErrorBag();
    }
}
