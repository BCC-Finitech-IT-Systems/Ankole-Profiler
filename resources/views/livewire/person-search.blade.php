<div>
    <style>
        .brand-btn { background-color: #982B55 !important; color: #fff !important; }
        .brand-btn:hover { background-color: #7a2244 !important; }
        .from-primary-600 { --tw-gradient-from: #982B55; }
        .to-primary-700 { --tw-gradient-to: #7a2244; }
        .text-primary-600, .text-primary-100 { color: #982B55; }
        .focus\:ring-primary-500:focus { --tw-ring-color: #982B55; }
    </style>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search Header --}}
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:justify-between mb-4">
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <h2 class="text-xl font-semibold text-gray-900">Search Persons</h2>

                {{-- Active Filter Profile Indicator --}}
                @if ($loadedProfileId)
                    @php
                        $currentProfile = collect($availableProfiles)->firstWhere('id', $loadedProfileId);
                    @endphp
                    @if ($currentProfile)
                        <div
                            class="flex items-center space-x-2 px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-lg">
                            <i class="fas fa-filter text-rose-600"></i>
                            <span class="text-sm font-medium text-rose-700">{{ $currentProfile['name'] }}</span>
                            <button wire:click="clearCurrentProfile" class="text-rose-400 hover:text-rose-600 ml-2"
                                title="Clear profile">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-2">
                @if ($hasActiveFilters)
                    <button wire:click="openCreateFilterDrawer"
                        class="inline-flex items-center px-4 py-2 brand-btn text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <i class="fas fa-save mr-2"></i>
                        Save as Filter Profile
                    </button>
                @endif

                <button wire:click="openViewFiltersDrawer"
                    class="inline-flex items-center px-4 py-2 brand-btn text-sm font-medium rounded-lg hover:opacity-90 transition-colors shadow-sm">
                    <i class="fas fa-folder-open mr-2"></i>
                    View Saved Filters
                    @if (count($availableProfiles) > 0)
                        <span class="ml-2 px-2 py-0.5 bg-white text-primary-600 rounded-full text-xs font-semibold">
                            {{ count($availableProfiles) }}
                        </span>
                    @endif
                </button>

                <button wire:click="toggleAdvanced"
                    class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-sliders-h mr-2"></i>
                    {{ $showAdvanced ? 'Hide' : 'Show' }} Advanced
                </button>

                <button wire:click="clearFilters"
                    class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Clear All
                </button>
            </div>
        </div>

        {{-- Info Banner --}}
        @if ($hasActiveFilters && !$loadedProfileId)
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-amber-600 mr-2"></i>
                    <span class="text-sm text-amber-800">
                        You have <strong>{{ count(array_filter($this->getCurrentFiltersArray())) }} active
                            filters</strong>.
                        Save them as a profile for quick access later.
                    </span>
                </div>
            </div>
        @endif

        {{-- Basic Search --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input wire:model.live.debounce.300ms="search" type="text" id="search"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter search term...">
            </div>

            <div>
                <label for="searchBy" class="block text-sm font-medium text-gray-700 mb-1">Search By</label>
                <select wire:model.live="searchBy" id="searchBy"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="name">Name</option>
                    <option value="person_id">Person ID</option>
                    <option value="phone">Phone</option>
                    <option value="email">Email</option>
                    <option value="identifier">Identifier</option>
                    <option value="global">All Fields</option>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="status" id="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        {{-- Advanced Filters --}}
        @if ($showAdvanced)
            <div class="border-t pt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Advanced Filters</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div>
                        <label for="classification"
                            class="block text-sm font-medium text-gray-700 mb-1">Classification</label>
                        <select wire:model.live="classification" id="classification"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Classifications</option>
                            @foreach ($classifications as $classificationOption)
                                <option value="{{ $classificationOption }}">{{ ucfirst($classificationOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select wire:model.live="gender" id="gender"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="OrganizationId"
                            class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                        <select wire:model.live="OrganizationId" id="OrganizationId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Organizations</option>
                            @foreach ($Organizations as $Organization)
                                <option value="{{ $Organization->id }}">{{ $Organization->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($OrganizationId)
                        <div>
                            <label for="roleType" class="block text-sm font-medium text-gray-700 mb-1">Role Type</label>
                            <select wire:model.live="roleType" id="roleType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Roles</option>
                                @foreach ($roleTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input wire:model.live.debounce.300ms="city" type="text" id="city"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="City">
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                        <input wire:model.live.debounce.300ms="district" type="text" id="district"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="District">
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <input wire:model.live.debounce.300ms="country" type="text" id="country"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Country">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age Range</label>
                        <div class="flex space-x-2">
                            <input wire:model.live.debounce.300ms="ageFrom" type="number" min="0"
                                max="120"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="From">
                            <input wire:model.live.debounce.300ms="ageTo" type="number" min="0"
                                max="120"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="To">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Results Section (Your existing table code) --}}
    <div class="bg-white shadow rounded-lg">
        {{-- Results Header --}}
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-row items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Search Results ({{ $persons->total() }} found)
                    </h3>

                    @if ($persons->count() > 0)
                        <div class="flex items-center space-x-2">
                            <input wire:model.live="selectAll" type="checkbox" id="selectAll"
                                class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded">
                            <label for="selectAll" class="text-sm text-gray-700">Select All</label>
                        </div>
                    @endif
                </div>

                <div class="flex items-center space-x-4">
                    @if (!empty($selectedPersons))
                        <button wire:click="exportSelected"
                            class="px-4 py-2 text-sm font-medium brand-btn rounded-md transition-colors">
                            Export Selected ({{ count($selectedPersons) }})
                        </button>
                    @endif

                    <div class="flex items-center space-x-2">
                        <label for="perPage" class="text-sm text-gray-700">Per page:</label>
                        <select wire:model.live="perPage" id="perPage"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Save Filter Profile Modal --}}
            @if ($showSaveFilterModal)
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Save Filter Profile</h3>
                                <button wire:click="closeSaveFilterModal" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <form wire:submit.prevent="saveFilterProfile">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Name
                                            *</label>
                                        <input wire:model="filterProfileName" type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="e.g., Active Patients in Kampala" required>
                                        @error('filterProfileName')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <textarea wire:model="filterProfileDescription" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="Optional description of this filter profile..."></textarea>
                                        @error('filterProfileDescription')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center">
                                        <input wire:model="isSharedProfile" type="checkbox" id="isShared"
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                        <label for="isShared" class="ml-2 text-sm text-gray-700">
                                            Share with other users in my organization
                                        </label>
                                    </div>

                                    {{-- Preview of current filters --}}
                                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Current Filters:</h4>
                                        <div class="space-y-1 text-xs text-gray-600">
                                            @php $currentFilters = $this->getCurrentFiltersArray(); @endphp
                                            @if (empty($currentFilters))
                                                <span class="text-gray-400">No active filters</span>
                                            @else
                                                @foreach ($currentFilters as $key => $value)
                                                    <div class="flex justify-between">
                                                        <span
                                                            class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span>{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex space-x-3 mt-6">
                                    <button type="button" wire:click="closeSaveFilterModal"
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="flex-1 px-4 py-2 brand-btn text-white rounded-lg hover:opacity-90 transition-colors">
                                        <i class="fas fa-save mr-2"></i>Save Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Load Filter Profile Modal --}}
            @if ($showLoadProfileModal)
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Load Filter Profile</h3>
                                <button wire:click="closeLoadProfileModal" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 overflow-y-auto max-h-96">
                            @if (empty($availableProfiles))
                                <div class="text-center py-8">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-folder-open text-gray-400 text-xl"></i>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 mb-2">No Filter Profiles Found</h4>
                                    <p class="text-gray-500">Create your first filter profile by applying some filters
                                        and clicking "Save Current Filters".</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($availableProfiles as $profile)
                                        <div
                                            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900">{{ $profile['name'] }}</h4>
                                                    @if ($profile['description'])
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            {{ $profile['description'] }}</p>
                                                    @endif

                                                    {{-- Show filter criteria --}}
                                                    @if (!empty($profile['filter_criteria']))
                                                        <div class="mt-2 flex flex-wrap gap-1">
                                                            @foreach ($profile['filter_criteria'] as $key => $value)
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                                    {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                                    {{ is_array($value) ? implode(', ', $value) : $value }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div
                                                        class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                        @if ($profile['user_id'] === Auth::id())
                                                            <span><i class="fas fa-user mr-1"></i>My Profile</span>
                                                        @else
                                                            <span><i class="fas fa-share mr-1"></i>Shared</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center space-x-2 ml-4">
                                                    <button wire:click="loadFilterProfile({{ $profile['id'] }})"
                                                        class="brand-btn border border-gray-500 px-3 py-1 rounded text-sm hover:opacity-90 transition-colors">
                                                        <i class="fas fa-download mr-1"></i>Load
                                                    </button>

                                                    @if ($profile['user_id'] === Auth::id())
                                                        <button wire:click="deleteFilterProfile({{ $profile['id'] }})"
                                                            class="text-red-600 hover:text-red-800 p-1"
                                                            onclick="return confirm('Are you sure you want to delete this filter profile?')"
                                                            title="Delete Profile">
                                                            <i class="fas fa-trash text-sm"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="p-6 border-t border-gray-200">
                            <button wire:click="closeLoadProfileModal"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if ($showSaveFilterModal)
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Save Filter Profile</h3>
                                <button wire:click="closeSaveFilterModal" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <form wire:submit.prevent="saveFilterProfile">
                                <div class="space-y-4">
                                    {{-- Profile Name with Smart Suggestions --}}
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-medium text-gray-700">Profile Name
                                                *</label>
                                            <button type="button" onclick="suggestProfileName()"
                                                class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                                                <i class="fas fa-magic mr-1"></i>Suggest Name
                                            </button>
                                        </div>
                                        <input wire:model="filterProfileName" type="text" id="profileNameInput"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="e.g., Active Patients in Kampala" required>
                                        @error('filterProfileName')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Description --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <textarea wire:model="filterProfileDescription" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="Optional description of this filter profile..."></textarea>
                                        @error('filterProfileDescription')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Sharing Option --}}
                                    <div class="flex items-center">
                                        <input wire:model="isSharedProfile" type="checkbox" id="isShared"
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                        <label for="isShared" class="ml-2 text-sm text-gray-700">
                                            Share with other users in my organization
                                        </label>
                                    </div>

                                    {{-- Current Filters Preview --}}
                                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                            <i class="fas fa-filter mr-2 text-gray-500"></i>Current Filters Preview
                                        </h4>
                                        <div class="space-y-2">
                                            @php $currentFilters = $this->getCurrentFiltersArray(); @endphp
                                            @if (empty($currentFilters))
                                                <span class="text-gray-400 text-sm">No active filters</span>
                                            @else
                                                @foreach ($currentFilters as $key => $value)
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="font-medium text-gray-600">
                                                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                        </span>
                                                        <span class="text-gray-800 bg-white px-2 py-1 rounded text-xs">
                                                            {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        </span>
                                                    </div>
                                                @endforeach

                                                {{-- Show estimated results count --}}
                                                <div class="mt-3 pt-3 border-t border-gray-200">
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="font-medium text-gray-600">Estimated
                                                            Results:</span>
                                                        <span class="text-primary-600 font-semibold">
                                                            {{ $persons->total() ?? 0 }} persons
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Quick Save Options --}}
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-blue-800 mb-2">Quick Actions</h5>
                                        <div class="space-y-2">
                                            <button type="button" onclick="saveAndUse()"
                                                class="w-full text-left px-3 py-2 text-sm text-blue-700 hover:bg-blue-100 rounded-lg transition-colors">
                                                <i class="fas fa-save mr-2"></i>Save and continue using these filters
                                            </button>
                                            <button type="button" onclick="saveAndCommunicate()"
                                                class="w-full text-left px-3 py-2 text-sm text-blue-700 hover:bg-blue-100 rounded-lg transition-colors">
                                                <i class="fas fa-paper-plane mr-2"></i>Save and use for communication
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex space-x-3 mt-6">
                                    <button type="button" wire:click="closeSaveFilterModal"
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="flex-1 px-4 py-2 brand-btn text-white rounded-lg hover:opacity-90 transition-colors font-medium">
                                        <i class="fas fa-save mr-2"></i>Save Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- JavaScript for enhanced functionality --}}
                <script>
                    function suggestProfileName() {
                        // Get current filter values
                        const currentFilters = @json($this->getCurrentFiltersArray());

                        // Generate smart name suggestion
                        let nameParts = [];

                        if (currentFilters.status && currentFilters.status !== 'active') {
                            nameParts.push(currentFilters.status.charAt(0).toUpperCase() + currentFilters.status.slice(1));
                        }

                        if (currentFilters.gender) {
                            nameParts.push(currentFilters.gender.charAt(0).toUpperCase() + currentFilters.gender.slice(1));
                        }

                        if (currentFilters.classification) {
                            nameParts.push(currentFilters.classification.charAt(0).toUpperCase() + currentFilters.classification.slice(
                                1));
                        }

                        if (currentFilters.role_type) {
                            nameParts.push(currentFilters.role_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
                        }

                        if (currentFilters.city) {
                            nameParts.push('in ' + currentFilters.city);
                        }

                        if (currentFilters.age_from || currentFilters.age_to) {
                            const ageFrom = currentFilters.age_from || '0';
                            const ageTo = currentFilters.age_to || '120';
                            nameParts.push(`Age ${ageFrom}-${ageTo}`);
                        }

                        let suggestedName;
                        if (nameParts.length === 0) {
                            const today = new Date();
                            suggestedName = `Custom Filter ${today.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
                        } else {
                            suggestedName = nameParts.join(' ');
                        }

                        // Set the suggested name
                        document.getElementById('profileNameInput').value = suggestedName;
                        @this.set('filterProfileName', suggestedName);
                    }

                    function saveAndUse() {
                        // Save the profile and continue with current search
                        @this.call('saveFilterProfile');
                    }

                    function saveAndCommunicate() {
                        // Save the profile and redirect to communication module
                        @this.call('saveProfileAndRedirectToCommunication');
                    }
                </script>
            @endif
        </div>

        {{-- Results Table --}}
        @if ($persons->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-[9px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input wire:model.live="selectAll" type="checkbox"
                                    class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded">
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name / Person ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Affiliations
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-[9px]">
                        @foreach ($persons as $person)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input wire:model.live="selectedPersons" type="checkbox"
                                        value="{{ $person->id }}"
                                        class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded">
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $person->full_name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $person->gender ? ucfirst($person->gender) : '' }}
                                        @if ($person->date_of_birth)
                                            @php
                                                $dob = $person->date_of_birth;
                                                if (is_string($dob)) {
                                                    try {
                                                        $dob = \Carbon\Carbon::parse($dob);
                                                    } catch (Exception $e) {
                                                        $dob = null;
                                                    }
                                                }
                                            @endphp
                                            @if ($dob)
                                                • {{ $dob->age }} years
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        • {{ $person->person_id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if ($person->primaryPhone())
                                        <div>{{ $person->primaryPhone()->number }}</div>
                                    @endif
                                    @if ($person->primaryEmail())
                                        <div>{{ $person->primaryEmail()->email }}</div>
                                    @endif
                                </td>
                                <td class="px-1.5 py-1">
                                    <div class="text-sm text-gray-900">
                                        @php
                                            $activeAffiliations = $person->affiliations->where('status', 'active');
                                            $totalAffiliations = $person->affiliations->count();
                                        @endphp
                                        @forelse($activeAffiliations as $affiliation)
                                            <div class="mb-1 flex items-start">
                                                <div class="flex items-center mr-2">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1"
                                                        title="Active affiliation"></div>
                                                </div>
                                                <div class="flex-1">
                                                    <span
                                                        class="font-medium">{{ $affiliation->Organization->display_name ?? $affiliation->Organization->legal_name ?? 'Not Provided' }}</span>
                                                    <span class="text-gray-500">-
                                                        {{ $affiliation->role_type }}</span>
                                                    @if ($affiliation->role_title)
                                                        <div class="text-xs text-gray-400">
                                                            {{ $affiliation->role_title }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="flex items-center">
                                                <div class="w-2 h-2 bg-gray-400 rounded-full mr-1"
                                                    title="No active affiliations"></div>
                                                <span class="text-gray-400 italic">No active affiliations</span>
                                            </div>
                                        @endforelse

                                        @if ($totalAffiliations > $activeAffiliations->count())
                                            <div class="text-xs text-orange-600 mt-1">
                                                {{ $totalAffiliations - $activeAffiliations->count() }} inactive
                                                affiliation(s)
                                            </div>
                                        @endif
                                    </div>
                                </td>


                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>{{ $person->city }}</div>
                                    @if ($person->district)
                                        <div class="text-gray-500">{{ $person->district }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if ($person->status === 'active') bg-green-100 text-green-800
                                        @elseif($person->status === 'inactive') bg-gray-100 text-gray-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($person->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        {{-- <a href="{{ route('persons.show', $person) }}"
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                        <a href="{{ route('persons.edit', $person) }}"
                                           class="text-rose-600 hover:text-rose-900">Edit</a> --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $persons->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-14.5l-.5.5" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No persons found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-6">
    {{-- Relationship Toggle Button --}}
    @if($persons->isNotEmpty())
        <div class="bg-gradient-to-r from-rose-50 to-pink-50 rounded-lg p-4 border border-rose-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center h-10 w-10 rounded-full bg-rose-100">
                        <i class="fas fa-project-diagram text-rose-600"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">View Related Connections</h4>
                        <p class="text-xs text-gray-600">
                            Discover relationships for the {{ $persons->total() }} filtered
                            {{ Str::plural('person', $persons->total()) }}
                        </p>
                    </div>
                </div>

                <button wire:click="toggleRelationships"
                        class="px-4 py-2 text-white rounded-lg text-sm font-medium transition-colors shadow-sm" style="background:#982B55;">
                    <i class="fas fa-{{ $showRelationships ? 'eye-slash' : 'eye' }} mr-2"></i>
                    {{ $showRelationships ? 'Hide' : 'Show' }} Relationships
                </button>
            </div>
        </div>
    @endif

    {{-- Relationship Grid Component --}}
    @if($showRelationships)
        <livewire:relationship-grid :personIds="$persons->pluck('id')->toArray()" :key="'rel-'.now()->timestamp" />
    @endif
</div>

    {{-- CREATE FILTER DRAWER --}}
    @if ($showCreateFilterDrawer)
        <div class="fixed inset-0 overflow-hidden z-50" wire:key="create-drawer">
            {{-- Backdrop --}}
            <div class="absolute inset-0 overflow-hidden">
                <div wire:click="closeViewFiltersDrawer"
                    class="absolute inset-0 bg-[rgba(173,216,230,0.25)] backdrop-blur-sm cursor-pointer transition duration-300">
                </div>

                {{-- Drawer Panel --}}
                <section class="absolute inset-y-0 right-0 pl-10 max-w-full flex">
                    <div class="w-screen max-w-md">
                        <div class="h-full flex flex-col bg-white shadow-xl">
                            {{-- Header --}}
                            <div class="px-6 py-6 bg-gradient-to-r from-green-600 to-green-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div
                                            class="flex items-center justify-center h-12 w-12 rounded-full bg-white bg-opacity-20">
                                            <i class="fas fa-save text-white text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h2 class="text-xl font-bold">Save Filter Profile</h2>
                                            <p class="text-sm text-green-100">Create a reusable filter configuration
                                            </p>
                                        </div>
                                    </div>
                                    <button wire:click="closeCreateFilterDrawer"
                                        class="text-white hover:text-gray-200 transition-colors">
                                        <i class="fas fa-times text-2xl"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 overflow-y-auto px-6 py-6">
                                <form wire:submit.prevent="saveFilterProfile">
                                    {{-- Profile Name --}}
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-semibold text-gray-900">
                                                Profile Name <span class="text-red-500">*</span>
                                            </label>
                                            <button type="button" wire:click="suggestProfileName"
                                                class="text-xs text-green-600 hover:text-green-700 font-medium">
                                                <i class="fas fa-magic mr-1"></i>Auto-generate
                                            </button>
                                        </div>
                                        <input wire:model="filterProfileName" type="text"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                            placeholder="e.g., Active Patients in Kampala" required>
                                        @error('filterProfileName')
                                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Description --}}
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-gray-900 mb-2">
                                            Description <span
                                                class="text-gray-400 text-xs font-normal">(Optional)</span>
                                        </label>
                                        <textarea wire:model="filterProfileDescription" rows="3"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                            placeholder="Describe what this filter profile is used for..."></textarea>
                                        @error('filterProfileDescription')
                                            <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Sharing Option --}}
                                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-start">
                                            <input wire:model="isSharedProfile" type="checkbox" id="isShared"
                                                class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                            <div class="ml-3">
                                                <label for="isShared"
                                                    class="text-sm font-medium text-gray-900 cursor-pointer">
                                                    Share with organization
                                                </label>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Other users in your organization can view and use this filter
                                                    profile
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Active Filters Preview --}}
                                    <div class="mb-6">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                            <i class="fas fa-filter mr-2 text-gray-500"></i>
                                            Active Filters Preview
                                        </h4>
                                        <div
                                            class="bg-rose-50 rounded-lg p-4 border border-rose-200">
                                            @php $currentFilters = $this->getCurrentFiltersArray(); @endphp
                                            @if (empty($currentFilters))
                                                <div class="text-center py-4">
                                                    <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                                                    <p class="text-gray-400 text-sm">No active filters</p>
                                                </div>
                                            @else
                                                <div class="space-y-2 mb-4">
                                                    @foreach ($currentFilters as $key => $value)
                                                        <div
                                                            class="flex items-center justify-between bg-white rounded-md px-3 py-2 shadow-sm">
                                                            <span class="text-xs font-medium text-gray-600">
                                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                            </span>
                                                            <span
                                                                class="text-xs font-semibold text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                                                {{ is_array($value) ? implode(', ', $value) : $value }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Results Count --}}
                                                <div class="pt-3 border-t border-rose-200">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-medium text-rose-900">
                                                            <i class="fas fa-users mr-2"></i>Results Found:
                                                        </span>
                                                        <span class="text-lg font-bold text-rose-600">
                                                            {{ $persons->total() ?? 0 }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="flex space-x-3">
                                        <button type="button" wire:click="closeCreateFilterDrawer"
                                            class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors shadow-md">
                                            <i class="fas fa-save mr-2"></i>Save Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @endif

    {{-- VIEW FILTERS DRAWER --}}
    @if ($showViewFiltersDrawer)
        <div class="fixed inset-0 overflow-hidden z-50" wire:key="view-drawer">
            {{-- Backdrop --}}
            <div class="absolute inset-0 overflow-hidden">
                <div wire:click="closeViewFiltersDrawer"
                    class="absolute inset-0 bg-[rgba(173,216,230,0.25)] backdrop-blur-sm cursor-pointer transition duration-300">
                </div>

                {{-- Drawer Panel --}}
                <section class="absolute inset-y-0 right-0 pl-10 max-w-full flex">
                    <div class="w-screen max-w-2xl">
                        <div class="h-full flex flex-col bg-white shadow-xl">
                            {{-- Header --}}
                            <div class="px-6 py-6 bg-gradient-to-r from-primary-600 to-primary-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div
                                            class="flex items-center justify-center h-12 w-12 rounded-full bg-red bg-opacity-2">
                                            <i class="fas fa-folder-open text-white text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <h2 class="text-xl font-bold">Saved Filter Profiles</h2>
                                            <p class="text-sm text-primary-100">
                                                {{ count($availableProfiles) }} profile(s) available
                                            </p>
                                        </div>
                                    </div>
                                    <button wire:click="closeViewFiltersDrawer"
                                        class="text-white hover:text-gray-200 transition-colors">
                                        <i class="fas fa-times text-2xl"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Search Bar --}}
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="profileSearch"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="Search filter profiles...">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 overflow-y-auto px-6 py-6">
                                @if (empty($availableProfiles))
                                    <div class="text-center py-12">
                                        <div
                                            class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No Filter Profiles Yet
                                        </h4>
                                        <p class="text-gray-500 mb-6">Create your first filter profile by applying
                                            filters and clicking "Save as Filter Profile"</p>
                                        <button wire:click="closeViewFiltersDrawer"
                                            class="inline-flex items-center px-4 py-2 brand-btn text-white rounded-lg hover:opacity-90 transition-colors">
                                            <i class="fas fa-plus mr-2"></i>Create First Profile
                                        </button>
                                    </div>
                                @else
                                    <div class="space-y-4">
                                        @foreach ($availableProfiles as $profile)
                                            <div
                                                class="group border-2 border-gray-200 rounded-xl p-5 hover:border-primary-300 hover:shadow-md transition-all duration-200">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        {{-- Profile Header --}}
                                                        <div class="flex items-center space-x-3 mb-2">
                                                            <h4 class="font-semibold text-gray-900 text-lg">
                                                                {{ $profile['name'] }}
                                                            </h4>
                                                            @if ($profile['is_shared'])
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    <i class="fas fa-share-alt mr-1"></i>Shared
                                                                </span>
                                                            @endif
                                                            @if ($loadedProfileId === $profile['id'])
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    <i class="fas fa-check mr-1"></i>Active
                                                                </span>
                                                            @endif
                                                        </div>

                                                        {{-- Description --}}
                                                        @if ($profile['description'])
                                                            <p class="text-sm text-gray-600 mb-3">
                                                                {{ $profile['description'] }}
                                                            </p>
                                                        @endif

                                                        {{-- Filter Badges --}}
                                                        @if (!empty($profile['filter_criteria']))
                                                            <div class="flex flex-wrap gap-2 mb-3">
                                                                @foreach ($profile['filter_criteria'] as $key => $value)
                                                                    <span
                                                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gradient-to-r from-rose-50 to-pink-50 text-rose-700 border border-rose-200">
                                                                        <i
                                                                            class="fas fa-tag mr-1.5 text-rose-400"></i>
                                                                        <span
                                                                            class="font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                        <span
                                                                            class="ml-1">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        {{-- Meta Information --}}
                                                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                            @if ($profile['user_id'] === Auth::id())
                                                                <span class="flex items-center">
                                                                    <i class="fas fa-user mr-1"></i>Created by you
                                                                </span>
                                                            @else
                                                                <span class="flex items-center">
                                                                    <i class="fas fa-users mr-1"></i>Shared profile
                                                                </span>
                                                            @endif
                                                            @if (isset($profile['usage_count']))
                                                                <span class="flex items-center">
                                                                    <i class="fas fa-chart-line mr-1"></i>Used
                                                                    {{ $profile['usage_count'] }} times
                                                                </span>
                                                            @endif
                                                            @if (isset($profile['last_used_at']))
                                                                <span class="flex items-center">
                                                                    <i
                                                                        class="fas fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($profile['last_used_at'])->diffForHumans() }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Action Buttons --}}
                                                    <div class="flex items-center space-x-2 ml-4">
                                                        @if ($loadedProfileId === $profile['id'])
                                                            <button wire:click="clearCurrentProfile"
                                                                class="px-3 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors border border-red-200"
                                                                title="Clear this profile">
                                                                <i class="fas fa-times mr-1"></i>Clear
                                                            </button>
                                                        @else
                                                            <button
                                                                wire:click="loadFilterProfile({{ $profile['id'] }})"
                                                                class="px-3 py-2 brand-btn text-white rounded-lg text-sm font-medium hover:opacity-90 transition-colors shadow-sm"
                                                                title="Load this profile">
                                                                <i class="fas fa-download mr-1"></i>Load
                                                            </button>
                                                        @endif

                                                        @if ($profile['user_id'] === Auth::id())
                                                            <button
                                                                wire:click="deleteFilterProfile({{ $profile['id'] }})"
                                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                                onclick="return confirm('Are you sure you want to delete this filter profile?')"
                                                                title="Delete Profile">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                <button wire:click="closeViewFiltersDrawer"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-white transition-colors">
                                    <i class="fas fa-times mr-2"></i>Close
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @endif

    {{-- Loading Indicator --}}
    <div wire:loading.delay class="fixed inset-0 flex items-center justify-center bg-transparent z-50">
        <div class="flex items-center space-x-3 bg-white shadow-lg rounded-lg p-4">
            <svg class="animate-spin h-6 w-6" style="color:#982B55;" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                    stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2
                     5.291A7.962 7.962 0 014 12H0c0 3.042 1.135
                     5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-gray-800 text-lg font-medium">Processing...</span>
        </div>
    </div>

</div>
