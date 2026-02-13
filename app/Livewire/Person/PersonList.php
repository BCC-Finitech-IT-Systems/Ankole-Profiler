<?php

namespace App\Livewire\Person;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Person;
use App\Models\FilterConfiguration;
use App\Services\PersonFilterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Services\SearchFilterService;

class PersonList extends Component
{
    use WithPagination;

    public $filters = [
        'search' => '',
        'classification' => '',
        'organization_id' => '',
        'age_range' => '',
        'gender' => '',
        'status' => '',
        'date_range' => ['start' => '', 'end' => '']
    ];

    public $dynamicFilters = [];
    public $showAdvancedFilters = false;

    // Delete person properties
    public $showDeleteModal = false;
    public $personToDeleteId = null;

        // Edit person properties
        public $editPersonId = null;
        public $editPersonData = [];
        // View person properties
        public $viewPersonId = null;
        public $viewPersonData = null;

    protected $queryString = [
        'filters' => ['except' => []],
        'showAdvancedFilters' => ['except' => false]
    ];

    public function mount()
    {
        $this->loadDynamicFilters();
    }

    // Remove the problematic Cache::remember call and fix the updatedFilters method
    public function updatedFilters()
    {
        $this->clearPersonListCache();
        $this->resetPage();
    }

    public function updatedDynamicFilters()
    {
        $this->clearPersonListCache();
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->filters = [
            'search' => '',
            'classification' => '',
            'organization_id' => '',
            'age_range' => '',
            'gender' => '',
            'status' => '',
            'date_range' => ['start' => '', 'end' => '']
        ];

        $this->dynamicFilters = [];
        $this->loadDynamicFilters();
        $this->clearPersonListCache();
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    private function clearPersonListCache()
    {
        // Clear specific cache patterns instead of Cache::flush()
        $user = Auth::user();
        $currentOrganization = user_current_organization();

        $isSuperAdmin = ($user instanceof \App\Models\User) && $user->hasRole('Super Admin');

        $patterns = [
            'person_list_' . md5(serialize([
                $this->filters,
                $this->dynamicFilters,
                $currentOrganization?->id ?? 'all',
                $isSuperAdmin ? 'all' : 'org'
            ])),
            'person_list_additional_' . ($currentOrganization?->id ?? 'all')
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function confirmDelete($personId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'You must be logged in to delete persons.'
                ]);
                return;
            }

            $person = Person::find($personId);
            if (!$person) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Person not found.'
                ]);
                return;
            }

            $currentOrganization = user_current_organization();
            $canViewAllPersons = ($user instanceof \App\Models\User) && $user->hasRole('Super Admin');

            if (!$canViewAllPersons && $currentOrganization) {
                $hasAffiliation = $person->affiliations()
                    ->where('organization_id', $currentOrganization->id)
                    ->exists();

                if (!$hasAffiliation) {
                    $this->dispatch('alert', [
                        'type' => 'error',
                        'message' => 'You do not have permission to delete this person.'
                    ]);
                    return;
                }
            }

            $this->personToDeleteId = $personId;
            $this->showDeleteModal = true;
        } catch (\Exception $e) {
            Log::error('Confirm delete exception: ' . $e->getMessage());
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'An error occurred while preparing to delete the person.'
            ]);
        }
    }

    public function deletePerson()
    {
        try {
            $user = Auth::user();
            if (!$user || !$this->personToDeleteId) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Invalid delete request.'
                ]);
                $this->cancelDelete();
                return;
            }

            $person = Person::find($this->personToDeleteId);
            if (!$person) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Person not found.'
                ]);
                $this->cancelDelete();
                return;
            }

            $currentOrganization = user_current_organization();
            $canViewAllPersons = ($user instanceof \App\Models\User) && $user->hasRole('Super Admin');

            if (!$canViewAllPersons && $currentOrganization) {
                $hasAffiliation = $person->affiliations()
                    ->where('organization_id', $currentOrganization->id)
                    ->exists();

                if (!$hasAffiliation) {
                    $this->dispatch('alert', [
                        'type' => 'error',
                        'message' => 'You do not have permission to delete this person.'
                    ]);
                    $this->cancelDelete();
                    return;
                }
            }

            $personName = $person->full_name;

            DB::beginTransaction();
            try {
                $affiliationIds = DB::table('person_affiliations')
                    ->where('person_id', $person->id)
                    ->pluck('id');

                if ($affiliationIds->isNotEmpty()) {
                    DB::table('staff_records')->whereIn('affiliation_id', $affiliationIds)->delete();
                    DB::table('student_records')->whereIn('affiliation_id', $affiliationIds)->delete();
                    DB::table('patient_records')->whereIn('affiliation_id', $affiliationIds)->delete();
                    DB::table('sacco_member_records')->whereIn('affiliation_id', $affiliationIds)->delete();
                    DB::table('parish_member_records')->whereIn('affiliation_id', $affiliationIds)->delete();
                }

                DB::table('phones')->where('person_id', $person->id)->delete();
                DB::table('email_addresses')->where('person_id', $person->id)->delete();
                DB::table('person_identifiers')->where('person_id', $person->id)->delete();
                DB::table('person_affiliations')->where('person_id', $person->id)->delete();

                $person->delete();
                DB::commit();

                $this->showDeleteModal = false;
                $this->personToDeleteId = null;

                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => "Person '{$personName}' has been successfully deleted."
                ]);
                $this->clearPersonListCache();
                $this->resetPage();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Delete person exception: ' . $e->getMessage());
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Failed to delete person: ' . $e->getMessage()
            ]);
            $this->cancelDelete();
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->personToDeleteId = null;
    }

        /**
         * Load a person's data for editing
         */
        public function editPerson($id)
        {
            try {
                $person = Person::findOrFail($id);
                $this->editPersonId = $person->id;
                $this->editPersonData = $person->toArray();
            } catch (\Exception $e) {
                Log::error('Error loading person for editing: ' . $e->getMessage());
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Failed to load person for editing.'
                ]);
            }
        }

            /**
             * Load a person's data for viewing
             */
            public function viewPerson($id)
            {
                $person = Person::find($id);
                if ($person) {
                    $this->viewPersonId = $person->id;
                    $this->viewPersonData = $person;
                } else {
                    $this->viewPersonId = null;
                    $this->viewPersonData = null;
                    $this->dispatch('alert', [
                        'type' => 'error',
                        'message' => 'Person not found.'
                    ]);
                }
            }

        /**
         * Update the person's data
         */
        public function updatePerson()
        {
            if (!$this->editPersonId) {
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'No person selected for update.'
                ]);
                return;
            }

            $this->validate([
                'editPersonData.given_name' => 'required|string|max:255',
                'editPersonData.family_name' => 'required|string|max:255',
                'editPersonData.date_of_birth' => 'required|date',
                'editPersonData.gender' => 'required|in:Male,Female',
                'editPersonData.address' => 'nullable|string|max:255',
                'editPersonData.city' => 'nullable|string|max:255',
                'editPersonData.country' => 'nullable|string|max:255',
                'editPersonData.district' => 'nullable|string|max:255',
                'editPersonData.email' => 'required|email|unique:users,email,' . $this->editPersonData['user_id'],
                'editPersonData.phone' => 'required|string|max:20|unique:phones,number,' . $this->editPersonData['id'] . ',person_id',
                // Add other fields as needed
            ]);

            try {
                DB::beginTransaction();

                // Update the person record
                $person = Person::findOrFail($this->editPersonId);
                $person->fill($this->editPersonData);
                $person->save();

                // Update the user record
                $user = $person->user;
                if ($user) {
                    $user->email = $this->editPersonData['email'];
                    $user->name = $this->editPersonData['given_name'] . ' ' . $this->editPersonData['family_name'];
                    $user->save();
                }

                // Update the person affiliations
                $affiliation = $person->affiliations()->first();
                if ($affiliation) {
                    $affiliation->update([
                        'organization_id' => $this->editPersonData['organization_id'] ?? $affiliation->organization_id,
                        'role_type' => $this->editPersonData['role_type'] ?? $affiliation->role_type,
                        'role_title' => $this->editPersonData['role_title'] ?? $affiliation->role_title,
                    ]);
                }

                // Update the email addresses
                $emailAddress = $person->emailAddresses()->first();
                if ($emailAddress) {
                    $emailAddress->update([
                        'email' => $this->editPersonData['email']
                    ]);
                }

                // Update the phone numbers
                $phone = $person->phones()->first();
                if ($phone) {
                    $phone->update([
                        'number' => $this->editPersonData['phone']
                    ]);
                }

                DB::commit();

                // Optionally reset edit state
                $this->editPersonId = null;
                $this->editPersonData = [];

                $this->dispatch('alert', [
                    'type' => 'success',
                    'message' => 'Person and related records updated successfully.'
                ]);

                // Optionally emit an event to refresh the list
                $this->emit('personUpdated');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating person: ' . $e->getMessage());
                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => 'Failed to update person and related records.'
                ]);
            }
        }

    protected function loadDynamicFilters()
    {
        $currentOrganization = user_current_organization();

        if ($currentOrganization) {
            $configurations = FilterConfiguration::activeForOrganization($currentOrganization->id)->get();

            foreach ($configurations as $config) {
                if (!isset($this->dynamicFilters[$config->field_name])) {
                    $this->dynamicFilters[$config->field_name] = '';
                }
            }
        }
    }

    public function render()
    {
        $user = Auth::user();
        $currentOrganization = user_current_organization();

            $canViewAllPersons = ($user instanceof \App\Models\User) && $user->hasRole('Super Admin');
            $canViewOrgPersons = ($user instanceof \App\Models\User) && $user->hasRole('Organization Admin');

        // Only allow Super Admins and Organization Admins to view persons
        if (!($user && method_exists($user, 'hasRole')) || (!$canViewAllPersons && !$canViewOrgPersons)) {
            return $this->renderEmptyState();
        }

        try {
            // For Organization Admins, always filter by their organization
            if ($canViewOrgPersons && !$canViewAllPersons && $currentOrganization) {
                $this->filters['organization_id'] = $currentOrganization->id;
            }

            // Allow Super Admins to clear organization filter
            if ($canViewAllPersons && isset($this->filters['organization_id']) && $this->filters['organization_id'] === '') {
                unset($this->filters['organization_id']);
            }

            // Merge filters, but always include search (even if empty)
            $allFilters = array_merge(['search' => $this->filters['search']], $this->filters, $this->dynamicFilters);
            $allFilters = array_filter($allFilters, function ($value, $key) {
                if ($key === 'search') return true; // Always include search
                if (is_array($value)) {
                    if (isset($value['start']) && isset($value['end'])) {
                        return !empty($value['start']) || !empty($value['end']);
                    }
                    return !empty(array_filter($value));
                }
                return $value !== '' && $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            // Use SearchFilterService instead of PersonFilterService
            $searchable = ['given_name', 'family_name', 'middle_name', 'person_id'];
            $service = new SearchFilterService(Person::class, $searchable);
            $service->applySearch($allFilters['search'] ?? '');

            // Remove 'search' from filters before applying the rest
            $filtersForService = $allFilters;
            unset($filtersForService['search']);
            $service->applyFilters($filtersForService);

            // Order by latest creation date
            $service->getQuery()->orderBy('created_at', 'desc');

            $persons = $service->paginate(6);
            // $persons->withPath(request()->url());

        } catch (\Exception $e) {
            Log::error('Error applying filters: ' . $e->getMessage(), [
                'filters' => $allFilters ?? [],
                'user_id' => $user->id ?? null
            ]);

            // Fallback query
            $query = Person::query();
            if ($canViewOrgPersons && !$canViewAllPersons && $currentOrganization) {
                $query->whereHas('affiliations', function ($q) use ($currentOrganization) {
                    $q->where('organization_id', $currentOrganization->id);
                });
            }

            // Order by latest creation date in fallback query
            $query->orderBy('created_at', 'desc');

            $persons = $query->paginate(10);
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'Some filters could not be applied. Showing basic results.'
            ]);
        }

        // Get additional data
        $additionalData = $this->getAdditionalData($currentOrganization, $canViewAllPersons);

        return view('livewire.person.person-list', array_merge([
            'persons' => $persons,
            'isSuperAdmin' => $canViewAllPersons,
            'currentOrganization' => $currentOrganization,
            'personToDelete' => $this->personToDeleteId ? Person::find($this->personToDeleteId) : null
        ], $additionalData));
    }

    private function getAdditionalData($currentOrganization, $canViewAllPersons)
    {
        $cacheKey = 'person_list_additional_' . ($currentOrganization?->id ?? 'all');

        return Cache::remember($cacheKey, 1800, function() use ($currentOrganization, $canViewAllPersons) {
            return [
                'availableRoles' => $this->getAvailableRolesForOrganization($currentOrganization),
                'genderOptions' => ['male', 'female', 'other', 'prefer_not_to_say'],
                'statusOptions' => ['active', 'inactive', 'suspended'],
                'ageRanges' => ['18-25', '26-35', '36-45', '46-55', '56-65', '65+'],
                'organizations' => $canViewAllPersons ?
                    \App\Models\Organization::select('id', 'legal_name')
                        ->where('is_active', true)
                        ->orderBy('legal_name')
                        ->get() :
                    ($currentOrganization ? collect([$currentOrganization]) : collect()),
                'filterConfigurations' => $currentOrganization ?
                    FilterConfiguration::activeForOrganization($currentOrganization->id)->get() :
                    collect()
            ];
        });
    }

    private function renderEmptyState()
    {
        return view('livewire.person.person-list', [
            'persons' => collect(),
            'availableRoles' => [],
            'genderOptions' => [],
            'statusOptions' => [],
            'ageRanges' => [],
            'organizations' => collect(),
            'filterConfigurations' => collect(),
            'isSuperAdmin' => false,
            'currentOrganization' => null,
            'personToDelete' => null
        ]);
    }

    private function getAvailableRolesForOrganization($organization)
    {
        if (!$organization) {
            return ['STAFF', 'MEMBER', 'VOLUNTEER', 'CONSULTANT', 'VENDOR'];
        }

        return match ($organization->category) {
            'hospital' => ['STAFF', 'PATIENT', 'VOLUNTEER', 'CONSULTANT', 'VENDOR'],
            'school' => ['STAFF', 'STUDENT', 'ALUMNI', 'GUARDIAN', 'VOLUNTEER', 'CONSULTANT'],
            'sacco' => ['STAFF', 'MEMBER', 'BOARD_MEMBER', 'CONSULTANT', 'VENDOR'],
            'parish' => ['STAFF', 'PARISHIONER', 'VOLUNTEER', 'CONSULTANT'],
            default => ['STAFF', 'MEMBER', 'VOLUNTEER', 'CONSULTANT', 'VENDOR']
        };
    }
}
