<?php

namespace App\Livewire\Communication;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CommunicationFilterProfile;
use App\Models\Person;
use App\Services\PersonFilterService;
use Illuminate\Support\Facades\Auth;
use App\Helpers\OrganizationHelper;
use App\Models\Organization;
use App\Traits\HandlesSweetAlerts;
use Illuminate\Support\Facades\Log;

class FilterProfiles extends Component
{
    use WithPagination, HandlesSweetAlerts;

    // Profile Management
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showPreviewModal = false;

    // Form Data
    public $profileId = null;
    public $name = '';
    public $description = '';
    public $is_shared = false;
    public $filter_criteria = [];
    public $profileToDelete = null;

    public $selectedOrganizationId = null;
    public $availableOrganizations = [];
    public $considerAllOrganizations = false;
    public $previewCount = 0;

    // Reset the form fields to their default state.
    public function resetForm()
    {
        $this->profileId = null;
        $this->name = '';
        $this->description = '';
        $this->is_shared = false;
        $this->filter_criteria = [];
    }

    // Filter Builder
    public $availableFilters = [
        'search' => 'Search Text',
        'classification' => 'Classification',
        'gender' => 'Gender',
        'age_range' => 'Age Range',
        'status' => 'Status',
        'city' => 'City',
        'district' => 'District',
        'county' => 'County',
        'subcounty' => 'Subcounty',
        'parish' => 'Parish',
        'village' => 'Village',
        'country' => 'Country'
    ];


    public function editProfile($profileId)
    {
        $this->openEditModal($profileId);
    }
    public $previewProfiles = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'is_shared' => 'boolean',
        'filter_criteria' => 'required|array|min:1'
    ];

    public function loadAvailableOrganizations()
    {
        $user = Auth::user();
        if ($user && $user->hasRole('Super Admin')) {
            $this->availableOrganizations = Organization::select('id')
                ->orderBy('id')
                ->get()
                ->toArray();
        } else {
            $this->availableOrganizations = [];
        }

        if (Auth::user()->hasRole('Super Admin')) {
            $this->availableOrganizations = Organization::select('id')
                ->where('is_active', 1)->orderBy('id')->get()->toArray();
        }
    }

    public function mount()
    {
        $this->resetForm();
        $this->selectedOrganizationId = OrganizationHelper::getCurrentOrganization()?->id;

        if (Auth::user()->hasRole('Super Admin')) {
            $this->availableOrganizations = Organization::select('id')
                ->where('is_active', 1)->orderBy('id')->get()->toArray();
        }
    }

    public function render()
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        if (!$organization) {
            // Handle the missing organization gracefully
            return view('livewire.communication.filter-profiles', [
                'profiles' => collect(),
                'organizationFieldOptions' => [],
                'error' => 'No organization selected or available.'
            ]);
        }
        try {
            if (!$organization) {
                throw new \Exception('No organization selected or available.');
            }

            $profiles = CommunicationFilterProfile::accessibleBy(Auth::id(), $organization->id)
                ->with(['user', 'Organization'])
                ->orderBy('last_used_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Get organization-specific field options
            $organizationFieldOptions = $this->getOrganizationFieldOptions($organization);

            return view('livewire.communication.filter-profiles', [
                'profiles' => $profiles,
                'organization' => $organization,
                'organizationFieldOptions' => $organizationFieldOptions,
                'error' => null
            ]);
        } catch (\Throwable $e) {
            // Log the error for debugging
            Log::error('FilterProfiles render error: ' . $e->getMessage());
            return view('livewire.communication.filter-profiles', [
                'profiles' => collect(),
                'organization' => null,
                'organizationFieldOptions' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($profileId)
    {
        $this->resetForm();
        $profile = $this->findProfile($profileId);

        if ($profile) {
            $this->profileId = $profile->id;
            $this->name = $profile->name;
            $this->description = $profile->description;
            $this->is_shared = $profile->is_shared;
            $this->filter_criteria = $profile->filter_criteria;
            $this->showEditModal = true;
        }
    }

    public function openDeleteModal($profileId)
    {
        $this->profileToDelete = $profileId;
    $this->showDeleteModal = true;

    // Debug log to ensure the value is set
    Log::info('Opening delete modal', [
        'profile_id' => $profileId,
        'profileToDelete' => $this->profileToDelete,
        'showDeleteModal' => $this->showDeleteModal
    ]);
    }

    public function openPreviewModal($profileId)
    {
        $profile = $this->findProfile($profileId);

        if ($profile) {
            $this->profileId = $profile->id;
            $this->name = $profile->name;
            $this->filter_criteria = $profile->filter_criteria;
            $this->updatePreviewCount();
            $this->showPreviewModal = true;
        }
    }

    public function deleteProfile()
    {
       try {
        Log::info('Delete profile called', [
            'profileToDelete' => $this->profileToDelete,
            'showDeleteModal' => $this->showDeleteModal
        ]);

        if (!$this->profileToDelete) {
            Log::warning('No profile selected for deletion');
            $this->showError('No profile selected for deletion.');
            $this->closeDeleteModal();
            return;
        }

        $profile = $this->findProfile($this->profileToDelete);

        if (!$profile) {
            Log::warning('Profile not found for deletion', [
                'profile_id' => $this->profileToDelete,
                'user_id' => Auth::id()
            ]);
            $this->showError('Profile not found or you do not have access to it.');
            $this->closeDeleteModal();
            return;
        }

        if (!$this->canManageProfile($profile)) {
            Log::warning('User attempted to delete profile without permission', [
                'profile_id' => $this->profileToDelete,
                'user_id' => Auth::id(),
                'profile_owner_id' => $profile->user_id
            ]);
            $this->showError('You do not have permission to delete this profile.');
            $this->closeDeleteModal();
            return;
        }

        $profileName = $profile->name;
        $deleted = $profile->delete();

        if ($deleted) {
            Log::info('Filter profile deleted successfully', [
                'profile_id' => $this->profileToDelete,
                'profile_name' => $profileName,
                'user_id' => Auth::id()
            ]);

            $this->showSuccess("Filter profile '{$profileName}' deleted successfully!");
            $this->dispatch('profileDeleted');
        } else {
            throw new \Exception('Failed to delete profile from database');
        }

    } catch (\Exception $e) {
        Log::error('Error deleting filter profile', [
            'profile_id' => $this->profileToDelete,
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $this->showError('An error occurred while deleting the profile. Please try again.');
    } finally {
        $this->closeDeleteModal();
    }
    }

    public function saveProfile()
    {
        $this->validate();

        $organization = OrganizationHelper::getCurrentOrganization();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => Auth::id(),
            'organization_id' => $organization->id,
            'filter_criteria' => $this->filter_criteria,
            'is_shared' => $this->is_shared,
            'is_active' => true
        ];

        if ($this->profileId) {
            // Update existing profile
            $profile = $this->findProfile($this->profileId);
            if ($profile && $this->canManageProfile($profile)) {
                $profile->update($data);
                $this->showSuccess('Filter profile updated successfully!');
            } else {
                $this->showError('You do not have permission to edit this profile.');
                return;
            }
        } else {
            // Create new profile
            $profile = CommunicationFilterProfile::create($data);
            $this->showSuccess('Filter profile created successfully!');
        }

        $this->closeModals();
        $this->resetForm();
    }

    public function saveProfileAndSendMessage()
    {
        $this->validate();

        $organization = OrganizationHelper::getCurrentOrganization();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => Auth::id(),
            'organization_id' => $organization->id,
            'filter_criteria' => $this->filter_criteria,
            'is_shared' => $this->is_shared,
            'is_active' => true
        ];

        if ($this->profileId) {
            // Update existing profile
            $profile = $this->findProfile($this->profileId);
            if ($profile && $this->canManageProfile($profile)) {
                $profile->update($data);
                $this->showSuccess('Filter profile updated successfully!');
            } else {
                $this->showError('You do not have permission to edit this profile.');
                return;
            }
        } else {
            // Create new profile
            $profile = CommunicationFilterProfile::create($data);
            $this->showSuccess('Filter profile created successfully! Redirecting to send message...');
        }

        $this->closeModals();
        $this->resetForm();

        // Redirect to send message page with the filter profile pre-selected
        return redirect()->route('communication.send')->with([
            'success' => 'Filter profile created successfully! You can now use it to send your message.',
            'preselect_filter_profile' => $profile->id
        ]);
    }


    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->profileToDelete = null;
    }


    public function toggleProfileStatus($profileId)
    {
        $profile = $this->findProfile($profileId);

        if ($profile && $this->canManageProfile($profile)) {
            $profile->update(['is_active' => !$profile->is_active]);
            $status = $profile->is_active ? 'activated' : 'deactivated';
            $this->showSuccess("Filter profile {$status} successfully!");
        } else {
            $this->showError('You do not have permission to modify this profile.');
        }
    }

    public function addFilterCriterion($field = null)
    {
        if (!$field) {
            $field = array_key_first($this->availableFilters);
        }

        $this->filter_criteria[$field] = '';
        $this->updatePreviewCount();
    }

    public function removeFilterCriterion($field)
    {
        unset($this->filter_criteria[$field]);
        $this->updatePreviewCount();
    }

    public function updatedFilterCriteria()
    {
        $this->updatePreviewCount();
    }

    protected function updatePreviewCount()
{
    if (empty($this->filter_criteria)) {
        $this->previewCount = 0;
        return;
    }

    try {
        if ($this->considerAllOrganizations && Auth::user()->hasRole('Super Admin')) {
            $baseQuery = Person::query();
        } else {
            $orgId = $this->selectedOrganizationId ?: OrganizationHelper::getCurrentOrganization()?->id;
            $baseQuery = Person::whereHas('affiliations', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        // Apply filters based on your existing data structure
        foreach (array_filter($this->filter_criteria) as $field => $value) {
            switch ($field) {
                case 'search':
                    $baseQuery->where(function($q) use ($value) {
                        $q->where('given_name', 'like', "%{$value}%")
                          ->orWhere('family_name', 'like', "%{$value}%");
                    });
                    break;
                case 'gender':
                case 'status':
                case 'city':
                case 'district':
                case 'country':
                    $baseQuery->where($field, $value);
                    break;
                case 'age_range':
                    if (preg_match('/(\d+)-(\d+)/', $value, $matches)) {
                        $minAge = $matches[1];
                        $maxAge = $matches[2];
                        $baseQuery->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$minAge, $maxAge]);
                    }
                    break;
                case 'role_type':
                    $baseQuery->whereHas('affiliations', function($q) use ($value) {
                        $q->where('role_type', $value);
                    });
                    break;
            }
        }

        $this->previewCount = $baseQuery->count();
    } catch (\Exception $e) {
        $this->previewCount = 0;
    }
}

    protected function findProfile($profileId)
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        return CommunicationFilterProfile::accessibleBy(Auth::id(), $organization->id)
            ->find($profileId);
    }

    protected function canManageProfile($profile)
    {
        // Users can manage their own profiles
        if ($profile->user_id === Auth::id()) {
            return true;
        }

        // For now, allow org admins to manage shared profiles in their organization
        // We can enhance permissions later
        $organization = OrganizationHelper::getCurrentOrganization();

        if ($profile->is_shared && $organization && $profile->organization_id === $organization->id) {
            return true;
        }

        return false;
    }


    // Get field options from the database for the current organization
    private function getOrganizationFieldOptions($organization)
    {
        if ($this->considerAllOrganizations && Auth::user()->hasRole('Super Admin')) {
            $baseQuery = Person::query();
        } else {
            $org = $organization ?: Organization::find($this->selectedOrganizationId);
            if (!$org) return $this->getEmptyFieldOptions();

            $baseQuery = Person::whereHas('affiliations', function ($q) use ($org) {
                $q->where('organization_id', $org->id);
            });
        }

        return [
            'district' => $baseQuery->clone()->whereNotNull('district')->distinct()->pluck('district')->filter()->sort()->values()->toArray(),
            'city' => $baseQuery->clone()->whereNotNull('city')->distinct()->pluck('city')->filter()->sort()->values()->toArray(),
            'gender' => $baseQuery->clone()->whereNotNull('gender')->distinct()->pluck('gender')->filter()->sort()->values()->toArray(),
            'status' => $baseQuery->clone()->whereNotNull('status')->distinct()->pluck('status')->filter()->sort()->values()->toArray(),
            'country' => $baseQuery->clone()->whereNotNull('country')->distinct()->pluck('country')->filter()->sort()->values()->toArray(),
            'county' => [],
            'subcounty' => [],
            'parish' => [],
            'village' => [],
        ];
    }

    public function updatedSelectedOrganizationId()
    {
        $this->updatePreviewCount();
    }

    public function updatedConsiderAllOrganizations()
    {
        $this->updatePreviewCount();
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showPreviewModal = false;
        $this->profileToDelete = null;
    }
}
