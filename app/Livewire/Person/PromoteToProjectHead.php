<?php

namespace App\Livewire\Person;

use Livewire\Component;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoteToProjectHead extends Component
{
    public $personId;
    public $person;
    public $selectedOrganizationId;
    public $availableOrganizations = [];
    public $currentAffiliations = [];
    public $isOrgAdmin = false;
    public $isSuperAdmin = false;
    public $canPromote = false;
    public $showModal = false;
    public $userDepartmentId = null;

    protected $listeners = ['openPromoteModal' => 'openModal'];

    public function mount($personId = null)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        $this->isSuperAdmin = $authUser && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('Super Admin');

        $this->isOrgAdmin = $authUser && method_exists($authUser, 'hasRole')
            && $authUser->hasRole('Organization Admin') && !$authUser->hasRole('Super Admin');

        // Both can promote to Project Head
        $this->canPromote = $this->isSuperAdmin || $this->isOrgAdmin;

        if ($personId) {
            $this->loadPerson($personId);
        }

        $this->loadAvailableOrganizations();
    }

    public function openModal($personId)
    {
        $this->loadPerson($personId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['personId', 'person', 'selectedOrganizationId', 'currentAffiliations']);
    }

    private function loadPerson($personId)
    {
        $this->personId = $personId;
        $this->person = Person::with(['user', 'affiliations.organization'])->find($personId);

        if ($this->person) {
            $this->currentAffiliations = $this->person->affiliations()
                ->where('status', 'active')
                ->with('organization')
                ->get()
                ->toArray();

            // Pre-select the first active affiliation's organization
            if (!empty($this->currentAffiliations)) {
                $this->selectedOrganizationId = $this->currentAffiliations[0]['organization_id'];
            }
        }
    }

    private function loadAvailableOrganizations()
    {
        $authUser = Auth::user();

        if ($this->isOrgAdmin && $authUser->person) {
            // Get the Org Admin's department from their affiliation
            $affiliation = PersonAffiliation::where('person_id', $authUser->person->id)
                ->where('status', 'active')
                ->whereNotNull('department_id')
                ->first();

            if ($affiliation && $affiliation->department_id) {
                $this->userDepartmentId = $affiliation->department_id;
                $department = \App\Models\Department::with('subCategories')->find($affiliation->department_id);

                $subCategoryNames = $department
                    ? $department->subCategories->pluck('name')->map(fn($n) => strtolower(trim($n)))->filter()->values()
                    : collect();

                if ($subCategoryNames->isNotEmpty()) {
                    $this->availableOrganizations = Organization::query()
                        ->where('is_super', false)
                        ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all())
                        ->orderBy('legal_name')
                        ->get()
                        ->toArray();
                }
            }
        } else {
            // Super Admin: load all organizations
            $this->availableOrganizations = Organization::where('is_super', false)
                ->orderBy('legal_name')
                ->get()
                ->toArray();
        }
    }

    public function isAlreadyProjectHead()
    {
        if (!$this->person || !$this->person->user) {
            return false;
        }

        return $this->person->user->hasRole('Project Head');
    }

    public function promoteToProjectHead()
    {
        if (!$this->canPromote) {
            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'You do not have permission to promote to Project Head.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
            return;
        }

        if (!$this->person || !$this->person->user) {
            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'Person or user account not found.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
            return;
        }

        $this->validate([
            'selectedOrganizationId' => 'required|exists:organizations,id',
        ], [
            'selectedOrganizationId.required' => 'Please select an organization/project.',
            'selectedOrganizationId.exists' => 'Invalid organization selected.',
        ]);

        // Validate organization is in scope for Org Admin
        if ($this->isOrgAdmin) {
            $orgIds = collect($this->availableOrganizations)->pluck('id')->toArray();
            if (!in_array($this->selectedOrganizationId, $orgIds)) {
                $this->dispatch('swal', [
                    'position' => 'top-end',
                    'icon' => 'error',
                    'title' => 'You can only promote persons to Project Head within your department scope.',
                    'showConfirmButton' => false,
                    'timer' => 2500
                ]);
                return;
            }
        }

        DB::beginTransaction();
        try {
            $user = $this->person->user;

            // Assign Project Head role if not already assigned
            if (!$user->hasRole('Project Head')) {
                $user->assignRole('Project Head');
                Log::info('Project Head role assigned', [
                    'user_id' => $user->id,
                    'person_id' => $this->person->id,
                    'promoted_by' => Auth::id(),
                ]);
            }

            // Check if person has an active affiliation with this organization
            $existingAffiliation = PersonAffiliation::where('person_id', $this->person->id)
                ->where('organization_id', $this->selectedOrganizationId)
                ->where('status', 'active')
                ->first();

            if ($existingAffiliation) {
                // Update existing affiliation to reflect Project Head role
                $existingAffiliation->update([
                    'role_title' => 'Project Head',
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Create new affiliation for this organization
                $departmentId = null;
                $selectedOrg = Organization::find($this->selectedOrganizationId);
                if ($selectedOrg && $selectedOrg->category) {
                    $subCategory = \App\Models\DepartmentSubCategory::whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [strtolower(trim($selectedOrg->category))]
                    )->first();
                    $departmentId = $subCategory?->department_id;
                }

                PersonAffiliation::create([
                    'person_id' => $this->person->id,
                    'organization_id' => $this->selectedOrganizationId,
                    'department_id' => $departmentId,
                    'role_type' => 'STAFF',
                    'role_title' => 'Project Head',
                    'start_date' => now(),
                    'status' => 'active',
                    'created_by' => Auth::id(),
                    'user_id' => $user->id,
                ]);
            }

            // Update person's classification to include PROJECT_HEAD
            $classification = json_decode($this->person->classification ?? '[]', true);
            if (!in_array('PROJECT_HEAD', $classification)) {
                $classification[] = 'PROJECT_HEAD';
                $this->person->update([
                    'classification' => json_encode($classification),
                ]);
            }

            DB::commit();

            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'success',
                'title' => $this->person->given_name . ' ' . $this->person->family_name . ' has been promoted to Project Head.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);

            $this->closeModal();
            $this->dispatch('personPromoted');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to promote to Project Head: ' . $e->getMessage(), [
                'person_id' => $this->personId,
                'organization_id' => $this->selectedOrganizationId,
                'exception' => $e,
            ]);

            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'Failed to promote to Project Head. Please try again.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
        }
    }

    public function revokeProjectHead()
    {
        if (!$this->canPromote) {
            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'You do not have permission to revoke Project Head role.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
            return;
        }

        if (!$this->person || !$this->person->user) {
            return;
        }

        DB::beginTransaction();
        try {
            $user = $this->person->user;

            // Remove Project Head role
            if ($user->hasRole('Project Head')) {
                $user->removeRole('Project Head');
                Log::info('Project Head role revoked', [
                    'user_id' => $user->id,
                    'person_id' => $this->person->id,
                    'revoked_by' => Auth::id(),
                ]);
            }

            // Update classification
            $classification = json_decode($this->person->classification ?? '[]', true);
            $classification = array_filter($classification, fn($c) => $c !== 'PROJECT_HEAD');
            $this->person->update([
                'classification' => json_encode(array_values($classification)),
            ]);

            // Update affiliations to remove Project Head title
            PersonAffiliation::where('person_id', $this->person->id)
                ->where('role_title', 'Project Head')
                ->where('status', 'active')
                ->update([
                    'role_title' => 'Staff',
                    'updated_by' => Auth::id(),
                ]);

            DB::commit();

            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'success',
                'title' => 'Project Head role has been revoked.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);

            $this->closeModal();
            $this->dispatch('personDemoted');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to revoke Project Head: ' . $e->getMessage(), [
                'person_id' => $this->personId,
                'exception' => $e,
            ]);

            $this->dispatch('swal', [
                'position' => 'top-end',
                'icon' => 'error',
                'title' => 'Failed to revoke Project Head role. Please try again.',
                'showConfirmButton' => false,
                'timer' => 2500
            ]);
        }
    }

    public function render()
    {
        return view('livewire.person.promote-to-project-head', [
            'availableOrganizations' => $this->availableOrganizations,
            'isAlreadyProjectHead' => $this->isAlreadyProjectHead(),
        ]);
    }
}
