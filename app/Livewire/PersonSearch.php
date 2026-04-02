<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Person;
use App\Models\CommunicationFilterProfile;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\OrganizationHelperNew as OrganizationHelper;
use Illuminate\Support\Facades\DB;

class PersonSearch extends Component
{
    use WithPagination;

    // Search properties
    public $search = '';
    public $searchBy = 'global';
    public $status = 'active';
    public $showAdvanced = false;

    // Advanced filters
    public $classification = '';
    public $gender = '';
    public $OrganizationId = '';
    public $roleType = '';
    public $city = '';
    public $district = '';
    public $country = '';
    public $ageFrom = '';
    public $ageTo = '';

    // Pagination
    public $perPage = 6;

    // Selection
    public $selectAll = false;
    public $selectedPersons = [];
    public $viewMode = 'grid';

    // Drawer states
    public $showCreateFilterDrawer = false;
    public $showViewFiltersDrawer = false;

    // Filter profile properties
    public $filterProfileName = '';
    public $filterProfileDescription = '';
    public $isSharedProfile = false;
    public $loadedProfileId = null;
    public $availableProfiles = [];
    public $profileSearch = '';
    public $showSaveFilterModal = false;

    // Data
    public $Organizations = [];
    public $classifications = [];
    public $roleTypes = [];

    // UI properties
    public $showLoadProfileModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'searchBy' => ['except' => 'global'],
        'classification' => ['except' => ''],
        'gender' => ['except' => ''],
        'OrganizationId' => ['except' => ''],
        'status' => ['except' => 'active'],
        'viewMode' => ['except' => 'grid'],
        'page' => ['except' => 1],
    ];

    protected $listeners = [
        'setStatus' => 'setStatus',
        'setViewMode' => 'setViewMode',
        'profileSaved' => 'refreshProfiles',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->loadOrganizations();
        $this->loadClassifications();
        $this->loadRoleTypes();
        $this->loadAvailableProfiles();
    }

    public function loadOrganizations()
    {
        $this->Organizations = Organization::active()->orderBy('legal_name')->get();
    }

    public function loadClassifications()
    {
        $this->classifications = Person::whereNotNull('classification')
            ->get()
            ->pluck('classification')
            ->flatMap(function ($item) {
                $decoded = json_decode($item, true);
                return is_array($decoded) ? $decoded : [$item];
            })
            ->unique()
            ->sort()
            ->values();
    }

    public function loadRoleTypes()
    {
        $this->roleTypes = [
            'patient' => 'Patient',
            'staff' => 'Staff',
            'student' => 'Student',
            'member' => 'Member',
            'volunteer' => 'Volunteer',
            'board_member' => 'Board Member',
            'administrator' => 'Administrator',
        ];
    }

    public function loadAvailableProfiles()
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        if (!$organization) {
            $this->availableProfiles = [];
            return;
        }

        $query = CommunicationFilterProfile::where(function ($q) {
            $q->where('user_id', Auth::id())
                ->orWhere('is_shared', true);
        })
            ->where('organization_id', $organization->id)
            ->where('is_active', true);

        if ($this->profileSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->profileSearch . '%')
                    ->orWhere('description', 'like', '%' . $this->profileSearch . '%');
            });
        }

        $this->availableProfiles = $query->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'filter_criteria' => $profile->filter_criteria,
                    'is_shared' => $profile->is_shared,
                    'user_id' => $profile->user_id,
                    'usage_count' => $profile->usage_count ?? 0,
                    'last_used_at' => $profile->last_used_at,
                ];
            })
            ->toArray();
    }

    public function updatedProfileSearch()
    {
        $this->loadAvailableProfiles();
    }

    public function getHasActiveFiltersProperty()
    {
        return !empty($this->search) ||
            !empty($this->classification) ||
            !empty($this->gender) ||
            !empty($this->OrganizationId) ||
            $this->status !== 'active' ||
            !empty($this->city) ||
            !empty($this->district) ||
            !empty($this->country) ||
            !empty($this->ageFrom) ||
            !empty($this->ageTo);
    }

    public function getCurrentFiltersArray()
    {
        $filters = [];

        if (!empty($this->search)) $filters['search'] = $this->search;
        if (!empty($this->searchBy) && $this->searchBy !== 'global') $filters['search_by'] = $this->searchBy;
        if (!empty($this->classification)) $filters['classification'] = $this->classification;
        if (!empty($this->gender)) $filters['gender'] = $this->gender;
        if (!empty($this->OrganizationId)) {
            $org = Organization::find($this->OrganizationId);
            $filters['organization_id'] = $this->OrganizationId;
            if ($org) $filters['Organization_name'] = $org->legal_name ?? $org->name;
        }
        if (!empty($this->roleType)) $filters['role_type'] = $this->roleType;
        if ($this->status !== 'active') $filters['status'] = $this->status;
        if (!empty($this->city)) $filters['city'] = $this->city;
        if (!empty($this->district)) $filters['district'] = $this->district;
        if (!empty($this->country)) $filters['country'] = $this->country;
        if (!empty($this->ageFrom)) $filters['age_from'] = $this->ageFrom;
        if (!empty($this->ageTo)) $filters['age_to'] = $this->ageTo;

        return $filters;
    }

    // Drawer methods
    public function openCreateFilterDrawer()
    {
        if (!$this->hasActiveFilters) {
            session()->flash('error', 'Please apply at least one filter before saving a profile.');
            return;
        }

        $this->showCreateFilterDrawer = true;
        $this->filterProfileName = '';
        $this->filterProfileDescription = '';
        $this->isSharedProfile = false;
    }

    public function closeCreateFilterDrawer()
    {
        $this->showCreateFilterDrawer = false;
        $this->reset(['filterProfileName', 'filterProfileDescription', 'isSharedProfile']);
    }

    public function openViewFiltersDrawer()
    {
        $this->showViewFiltersDrawer = true;
        $this->loadAvailableProfiles();
    }

    public function closeViewFiltersDrawer()
    {
        $this->showViewFiltersDrawer = false;
        $this->profileSearch = '';
    }

    public function suggestProfileName()
    {
        $currentFilters = $this->getCurrentFiltersArray();
        $nameParts = [];

        if (isset($currentFilters['status']) && $currentFilters['status'] !== 'active') {
            $nameParts[] = ucfirst($currentFilters['status']);
        }

        if (isset($currentFilters['gender'])) {
            $nameParts[] = ucfirst($currentFilters['gender']);
        }

        if (isset($currentFilters['classification'])) {
            $nameParts[] = ucfirst($currentFilters['classification']);
        }

        if (isset($currentFilters['role_type'])) {
            $nameParts[] = ucwords(str_replace('_', ' ', $currentFilters['role_type']));
        }

        if (isset($currentFilters['Organization_name'])) {
            $nameParts[] = 'at ' . $currentFilters['Organization_name'];
        }

        if (isset($currentFilters['city'])) {
            $nameParts[] = 'in ' . $currentFilters['city'];
        }

        if (isset($currentFilters['age_from']) || isset($currentFilters['age_to'])) {
            $ageFrom = $currentFilters['age_from'] ?? '0';
            $ageTo = $currentFilters['age_to'] ?? '120';
            $nameParts[] = "Age {$ageFrom}-{$ageTo}";
        }

        if (empty($nameParts)) {
            $this->filterProfileName = 'Custom Filter ' . now()->format('M d');
        } else {
            $this->filterProfileName = implode(' ', $nameParts);
        }
    }

    public function saveFilterProfile()
    {
        $this->validate([
            'filterProfileName' => 'required|string|max:255',
            'filterProfileDescription' => 'nullable|string|max:1000',
        ]);

        $organization = OrganizationHelper::getCurrentOrganization();

        if (!$organization) {
            session()->flash('error', 'No organization selected. Cannot save filter profile.');
            return;
        }

        try {
            $profile = CommunicationFilterProfile::create([
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'name' => $this->filterProfileName,
                'description' => $this->filterProfileDescription,
                'filter_criteria' => $this->getCurrentFiltersArray(),
                'is_shared' => $this->isSharedProfile,
                'is_active' => true,
                'usage_count' => 0,
                'last_used_at' => now(),
            ]);

            $this->loadedProfileId = $profile->id;
            $this->closeCreateFilterDrawer();
            $this->loadAvailableProfiles();

            session()->flash('success', 'Filter profile saved successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save filter profile: ' . $e->getMessage());
        }
    }

    public function loadFilterProfile($profileId)
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        $profile = CommunicationFilterProfile::accessibleBy(Auth::id(), $organization->id)
            ->find($profileId);

        if (!$profile) {
            session()->flash('error', 'Filter profile not found or you do not have access to it.');
            return;
        }

        try {
            // Clear existing filters first
            $this->clearFilters();

            // Apply filters from profile
            $criteria = $profile->filter_criteria;

            $this->search = $criteria['search'] ?? '';
            $this->searchBy = $criteria['search_by'] ?? 'global';
            $this->status = $criteria['status'] ?? 'active';
            $this->classification = $criteria['classification'] ?? '';
            $this->gender = $criteria['gender'] ?? '';
            $this->OrganizationId = $criteria['organization_id'] ?? '';
            $this->roleType = $criteria['role_type'] ?? '';
            $this->city = $criteria['city'] ?? '';
            $this->district = $criteria['district'] ?? '';
            $this->country = $criteria['country'] ?? '';
            $this->ageFrom = $criteria['age_from'] ?? '';
            $this->ageTo = $criteria['age_to'] ?? '';

            // Update usage statistics
            $profile->update([
                'usage_count' => ($profile->usage_count ?? 0) + 1,
                'last_used_at' => now()
            ]);

            $this->loadedProfileId = $profileId;
            $this->closeViewFiltersDrawer();
            $this->loadAvailableProfiles();
            $this->resetPage();

            session()->flash('success', "Filter profile '{$profile->name}' loaded successfully!");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load filter profile: ' . $e->getMessage());
        }
    }

    public function clearCurrentProfile()
    {
        $this->loadedProfileId = null;
        $this->clearFilters();
        session()->flash('success', 'Filter profile cleared.');
    }

    public function deleteFilterProfile($profileId)
    {
        $organization = OrganizationHelper::getCurrentOrganization();

        $profile = CommunicationFilterProfile::where('id', $profileId)
            ->where('organization_id', $organization->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$profile) {
            session()->flash('error', 'You can only delete your own filter profiles.');
            return;
        }

        try {
            $profileName = $profile->name;
            $profile->delete();

            if ($this->loadedProfileId === $profileId) {
                $this->loadedProfileId = null;
            }

            $this->loadAvailableProfiles();

            session()->flash('success', "Filter profile '{$profileName}' deleted successfully!");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete filter profile: ' . $e->getMessage());
        }
    }

    public function saveProfileAndRedirectToCommunication()
    {
        $this->validate([
            'filterProfileName' => 'required|string|max:255',
            'filterProfileDescription' => 'nullable|string|max:500',
        ]);

        $organization = OrganizationHelper::getCurrentOrganization();

        if (!$organization) {
            session()->flash('error', 'No organization selected. Cannot save filter profile.');
            return;
        }

        try {
            $profile = CommunicationFilterProfile::create([
                'name' => $this->filterProfileName,
                'description' => $this->filterProfileDescription,
                'user_id' => Auth::id(),
                'organization_id' => $organization->id,
                'filter_criteria' => $this->getCurrentFiltersArray(),
                'is_shared' => $this->isSharedProfile,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            $this->closeCreateFilterDrawer();

            return redirect()->route('communication.send')->with([
                'success' => 'Filter profile saved successfully! You can now use it to send communications.',
                'preselect_filter_profile' => $profile->id
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save filter profile. Please try again.');
        }
    }

    // Existing methods from your original code
    public function updatingSearch()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function updatingSearchBy()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function updatingClassification()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function updatingGender()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function updatingOrganizationId()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function updatingStatus()
    {
        $this->resetPage();
        $this->loadedProfileId = null;
    }

    public function toggleAdvanced()
    {
        $this->showAdvanced = !$this->showAdvanced;
    }

    public function clearFilters()
    {
        $this->reset([
            'search',
            'classification',
            'gender',
            'OrganizationId',
            'roleType',
            'city',
            'district',
            'country',
            'ageFrom',
            'ageTo'
        ]);

        $this->status = 'active';
        $this->searchBy = 'global';
        $this->loadedProfileId = null;
        $this->resetPage();

        $this->dispatch('filtersCleared');
        session()->flash('success', 'All filters cleared successfully.');
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedPersons = $this->getPersonsQuery()->pluck('id')->toArray();
        } else {
            $this->selectedPersons = [];
        }
    }

    public function getPersonsQuery(): Builder
    {
        $query = Person::query()
            ->with([
                'phones' => function ($query) {
                    $query->where('is_primary', true);
                },
                'emailAddresses' => function ($query) {
                    $query->where('is_primary', true);
                },
                'identifiers',
                'affiliations' => function ($query) {
                    $query->where('status', 'active');
                }
            ]);

        // Apply search based on search type
        if (!empty($this->search)) {
            switch ($this->searchBy) {
                case 'name':
                    $query->where(function ($q) {
                        $q->where('given_name', 'like', "%{$this->search}%")
                            ->orWhere('family_name', 'like', "%{$this->search}%")
                            ->orWhere('middle_name', 'like', "%{$this->search}%");
                    });
                    break;

                case 'person_id':
                    $query->where('person_id', 'like', "%{$this->search}%");
                    break;

                case 'phone':
                    $query->whereHas('phones', function ($q) {
                        $q->where('number', 'like', "%{$this->search}%");
                    });
                    break;

                case 'email':
                    $query->whereHas('emailAddresses', function ($q) {
                        $q->where('email', 'like', "%{$this->search}%");
                    });
                    break;

                case 'identifier':
                    $query->whereHas('identifiers', function ($q) {
                        $q->where('identifier', 'like', "%{$this->search}%");
                    });
                    break;

                case 'global':
                default:
                    $query->where(function ($q) {
                        $q->where('given_name', 'like', "%{$this->search}%")
                            ->orWhere('family_name', 'like', "%{$this->search}%")
                            ->orWhere('middle_name', 'like', "%{$this->search}%")
                            ->orWhere('person_id', 'like', "%{$this->search}%")
                            ->orWhereHas('phones', function ($phoneQuery) {
                                $phoneQuery->where('number', 'like', "%{$this->search}%");
                            })
                            ->orWhereHas('emailAddresses', function ($emailQuery) {
                                $emailQuery->where('email', 'like', "%{$this->search}%");
                            })
                            ->orWhereHas('identifiers', function ($identifierQuery) {
                                $identifierQuery->where('identifier', 'like', "%{$this->search}%");
                            })
                            ->orWhere('address', 'like', "%{$this->search}%")
                            ->orWhere('city', 'like', "%{$this->search}%")
                            ->orWhere('district', 'like', "%{$this->search}%");
                    });
                    break;
            }
        }

        // Apply filters
        if (!empty($this->classification)) {
            $query->whereJsonContains('classification', $this->classification);
        }

        if (!empty($this->gender)) {
            $query->where('gender', $this->gender);
        }

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->city)) {
            $query->where('city', 'like', "%{$this->city}%");
        }

        if (!empty($this->district)) {
            $query->where('district', 'like', "%{$this->district}%");
        }

        if (!empty($this->country)) {
            $query->where('country', 'like', "%{$this->country}%");
        }

        // Organization filter
        if (!empty($this->OrganizationId)) {
            $query->whereHas('affiliations', function ($q) {
                $q->where('organization_id', $this->OrganizationId)
                    ->where('status', 'active');

                if (!empty($this->roleType)) {
                    $q->where('role_type', $this->roleType);
                }
            });
        }

        // Age filters
        if (!empty($this->ageFrom) || !empty($this->ageTo)) {
            $query->where(function ($q) {
                if (!empty($this->ageFrom)) {
                    $dateFrom = now()->subYears($this->ageFrom)->format('Y-m-d');
                    $q->where('date_of_birth', '<=', $dateFrom);
                }

                if (!empty($this->ageTo)) {
                    $dateTo = now()->subYears($this->ageTo)->format('Y-m-d');
                    $q->where('date_of_birth', '>=', $dateTo);
                }
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getPersonsProperty()
    {
        return $this->getPersonsQuery()->paginate($this->perPage);
    }

    public function getOrganizationsProperty()
    {
        return Organization::active()->orderBy('legal_name')->get();
    }

    public function getClassificationsProperty()
    {
        return Person::whereNotNull('classification')
            ->get()
            ->pluck('classification')
            ->flatMap(function ($item) {
                $decoded = json_decode($item, true);
                return is_array($decoded) ? $decoded : [$item];
            })
            ->unique()
            ->sort()
            ->values();
    }

    public function getRoleTypesProperty()
    {
        return [
            'patient' => 'Patient',
            'staff' => 'Staff',
            'student' => 'Student',
            'member' => 'Member',
            'volunteer' => 'Volunteer',
            'board_member' => 'Board Member',
            'administrator' => 'Administrator',
        ];
    }

    public function exportSelected()
    {
        if (empty($this->selectedPersons)) {
            session()->flash('error', 'No persons selected for export.');
            return;
        }

        try {
            $persons = Person::whereIn('id', $this->selectedPersons)
                ->with(['phones', 'emailAddresses', 'identifiers', 'affiliations'])
                ->get();

            session()->flash('success', count($this->selectedPersons) . ' persons exported successfully.');

            $this->selectedPersons = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function getSearchSummaryProperty()
    {
        $activeFilters = [];

        if (!empty($this->search)) {
            $activeFilters[] = "Search: \"{$this->search}\"";
        }

        if (!empty($this->status)) {
            $activeFilters[] = "Status: " . ucfirst($this->status);
        }

        if (!empty($this->gender)) {
            $activeFilters[] = "Gender: " . ucfirst($this->gender);
        }

        if (!empty($this->classification)) {
            $activeFilters[] = "Classification: " . ucfirst($this->classification);
        }

        if (!empty($this->city)) {
            $activeFilters[] = "City: {$this->city}";
        }

        return implode(' • ', $activeFilters);
    }

    public function refreshProfiles()
    {
        $this->loadAvailableProfiles();
    }

    public $showRelationships = false;

    public function toggleRelationships()
    {
        $this->showRelationships = !$this->showRelationships;

        if ($this->showRelationships) {
            $personIds = $this->getPersonsQuery()->pluck('id')->toArray();
            $this->dispatch('loadRelationships', personIds: $personIds);
        }
    }



    // Also add this computed property:
    public function getRelationshipCountProperty()
    {
        if (empty($this->persons) || $this->persons->isEmpty()) {
            return 0;
        }

        $personIds = $this->persons->pluck('id')->toArray();

        return DB::table('person_relations')
            ->whereIn('person_id', $personIds)
            ->distinct('related_person_id')
            ->count('related_person_id');
    }

    public function render()
    {
        return view('livewire.person-search', [
            'persons' => $this->persons,
            'Organizations' => $this->Organizations,
            'classifications' => $this->classifications,
            'roleTypes' => $this->roleTypes,
            'hasActiveFilters' => $this->hasActiveFilters,
            'availableProfiles' => $this->availableProfiles,
        ]);
    }
}
