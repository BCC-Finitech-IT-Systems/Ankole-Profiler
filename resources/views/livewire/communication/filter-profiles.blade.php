<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Communication</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Filter Profiles</h1>
            </div>
        </div>
    </x-slot>

    <!-- Two Column Layout -->
    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-12 gap-6">
                <!-- Left Column - Filter Profiles Table -->
                <div class="col-span-12">
                    @if ($profiles->count() > 0)
                        <!-- Table Header -->
                        <div class="bg-white rounded-t-xl shadow-sm border border-gray-200 p-6 mb-0">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                        <div class="p-2 bg-gray-200 rounded-lg mr-3">
                                            <svg class="w-5 h-5 text-blue-100" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                                </path>
                                            </svg>
                                        </div>
                                        Filter Profiles
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1 flex items-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-black mr-2">
                                            {{ $profiles->total() }}
                                        </span>
                                        profile(s) found
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    {{-- <div class="text-xs text-gray-500">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Create filters on the right →
                                    </div> --}}

                                    <div class="drawer drawer-end">
                                        <input id="my-drawer-5" type="checkbox" class="drawer-toggle"
                                            @if ($showCreateModal || $showEditModal) checked @endif />
                                        <div class="drawer-content">
                                            <!-- Page content here -->
                                            <label for="my-drawer-5" class="drawer-button btn border-0 text-white"
                                                style="background:#982B55;">
                                                @if ($showEditModal)
                                                    Update Filter
                                                @else
                                                    Create Filter
                                                @endif
                                            </label>
                                        </div>
                                        <div class="drawer-side">
                                            <!-- Overlay to close drawer when clicking outside -->
                                            <label for="my-drawer-5" class="drawer-overlay"></label>
                                            <!-- Optional close button inside the drawer -->
                                            <button class="btn btn-ghost absolute top-4 right-4" type="button">
                                                <label for="my-drawer-5" class="cursor-pointer">Close</label>
                                            </button>
                                            <div class="col-span-6">
                                                <div
                                                    class="bg-white shadow-lg rounded-xl border border-gray-200 sticky top-6 overflow-hidden">
                                                    <!-- Header Section with Gradient -->
                                                    <div class="bg-gray-100 text-black p-6">
                                                        <h3 class="text-xl font-bold mb-2 flex items-center">
                                                            <div class="p-2 bg-opacity-20 rounded-lg mr-3">
                                                                <svg class="w-6 h-6" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4">
                                                                    </path>
                                                                </svg>
                                                            </div>
                                                            Create Filter Profile
                                                        </h3>
                                                        <p class="text-gray-700 text-sm">Build targeted communication
                                                            filters for your organization</p>
                                                    </div>

                                                    <!-- Form Content -->
                                                    <div class="p-6">
                                                        <form wire:submit.prevent="saveProfile" class="space-y-6">
                                                            <!-- Basic Information Section -->
                                                            <div
                                                                class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                                                                <h4
                                                                    class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                                                    <svg class="w-5 h-5 mr-2 text-black" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                        </path>
                                                                    </svg>
                                                                    Basic Information
                                                                </h4>
                                                                <div class="space-y-4">
                                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                        <div class="form-control">
                                                                            <label class="label">
                                                                                <span
                                                                                    class="label-text font-medium text-gray-700">Profile
                                                                                    Name <span
                                                                                        class="text-red-500">*</span></span>
                                                                            </label>
                                                                            <input type="text" wire:model="name"
                                                                                class="input input-bordered w-full focus:ring-2 focus:ring-black focus:border-black transition-all duration-200"
                                                                                placeholder="e.g., Students Age 18-25">
                                                                            @error('name')
                                                                                <span
                                                                                    class="text-red-500 text-sm mt-1 flex items-center">
                                                                                    <svg class="w-4 h-4 mr-1"
                                                                                        fill="currentColor"
                                                                                        viewBox="0 0 20 20">
                                                                                        <path fill-rule="evenodd"
                                                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                                                            clip-rule="evenodd"></path>
                                                                                    </svg>
                                                                                    {{ $message }}
                                                                                </span>
                                                                            @enderror
                                                                        </div>

                                                                        <div class="form-control">
                                                                            <label class="label">
                                                                                <span
                                                                                    class="label-text font-medium text-gray-700">Description</span>
                                                                            </label>
                                                                            <textarea wire:model="description"
                                                                                class="textarea textarea-bordered focus:ring-2 focus:ring-black focus:border-black transition-all duration-200"
                                                                                rows="3" placeholder="Optional description"></textarea>
                                                                            @error('description')
                                                                                <span
                                                                                    class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                                                            @enderror
                                                                        </div>
                                                                    </div>

                                                                    <div class="form-control">
                                                                        <label
                                                                            class="label cursor-pointer justify-start gap-3 bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                                                                            <input type="checkbox"
                                                                                wire:model="is_shared"
                                                                                class="toggle toggle-primary">
                                                                            <div>
                                                                                <span
                                                                                    class="label-text font-medium text-gray-700">Share
                                                                                    with organization</span>
                                                                                <p class="text-xs text-gray-500 mt-1">
                                                                                    Allow other users in your
                                                                                    organization to use this filter
                                                                                    profile</p>
                                                                            </div>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @if (Auth::user()->hasRole('Super Admin') && count($availableOrganizations) > 0)
                                                                <div class="card bg-base-200/50 shadow-sm">
                                                                    <div class="card-body p-6">
                                                                        <h4
                                                                            class="card-title text-lg mb-4 flex items-center">
                                                                            <svg class="w-5 h-5 mr-2 text-warning"
                                                                                fill="none" stroke="currentColor"
                                                                                viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m2 0v-4a2 2 0 012-2h2a2 2 0 012 2v4">
                                                                                </path>
                                                                            </svg>
                                                                            Organization Scope
                                                                        </h4>

                                                                        <div class="space-y-4">
                                                                            <!-- Consider All Organizations Toggle -->
                                                                            <div class="form-control">
                                                                                <label
                                                                                    class="label cursor-pointer justify-start gap-3">
                                                                                    <input type="checkbox"
                                                                                        wire:model.live="considerAllOrganizations"
                                                                                        class="toggle toggle-warning">
                                                                                    <div>
                                                                                        <span
                                                                                            class="label-text font-medium">Include
                                                                                            all organizations</span>
                                                                                        <p
                                                                                            class="text-xs text-gray-500 mt-1">
                                                                                            Create filter across all
                                                                                            organizations (Super Admin
                                                                                            only)</p>
                                                                                    </div>
                                                                                </label>
                                                                            </div>

                                                                            <!-- Organization Selector (when not considering all) -->
                                                                            @if (!$considerAllOrganizations)
                                                                                <div class="form-control">
                                                                                    <label class="label">
                                                                                        <span
                                                                                            class="label-text font-small">Target
                                                                                            Organization</span>
                                                                                    </label>
                                                                                    <select
                                                                                        wire:model.live="selectedOrganizationId"
                                                                                        class="select select-bordered w-full">
                                                                                        <option value="">Select
                                                                                            Organization</option>
                                                                                        @foreach ($availableOrganizations as $org)
                                                                                            <option
                                                                                                value="{{ $org['id'] }}">
                                                                                                {{ $org['display_name'] }}
                                                                                            </option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Filter Criteria Section -->
                                                            <div
                                                                class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                                                                <h4
                                                                    class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                                                    <svg class="w-5 h-5 mr-2 text-black"
                                                                        fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                                                        </path>
                                                                    </svg>
                                                                    Filter Criteria <span class="text-red-500">*</span>
                                                                </h4>

                                                                <div class="space-y-4">
                                                                    <!-- Add Filter Dropdown -->
                                                                    <div class="dropdown w-full">
                                                                        <button type="button" tabindex="0"
                                                                            class="btn btn-outline w-full justify-between hover:bg-gray-100 transition-all duration-200 border-2 border-dashed border-gray-300 hover:border-black">
                                                                            <span class="flex items-center gap-2">
                                                                                <div
                                                                                    class="p-1 bg-gray-200 rounded-full">
                                                                                    <svg class="w-4 h-4 text-black"
                                                                                        fill="none"
                                                                                        stroke="currentColor"
                                                                                        viewBox="0 0 24 24">
                                                                                        <path stroke-linecap="round"
                                                                                            stroke-linejoin="round"
                                                                                            stroke-width="2"
                                                                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                                                                                        </path>
                                                                                    </svg>
                                                                                </div>
                                                                                <span class="font-medium">Add Filter
                                                                                    Criterion</span>
                                                                            </span>
                                                                            <svg class="w-4 h-4 text-black"
                                                                                fill="none" stroke="currentColor"
                                                                                viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M19 9l-7 7-7-7"></path>
                                                                            </svg>
                                                                        </button>
                                                                        <ul tabindex="0"
                                                                            class="dropdown-content menu p-2 shadow-xl bg-white rounded-xl w-full border border-gray-200 max-h-48 overflow-y-auto z-10">
                                                                            @foreach ($availableFilters as $key => $label)
                                                                                @if (!array_key_exists($key, $filter_criteria))
                                                                                    <li>
                                                                                        <a wire:click="addFilterCriterion('{{ $key }}')"
                                                                                            class="gap-3 text-sm hover:bg-gray-100 rounded-lg p-3 transition-colors">
                                                                                            <div
                                                                                                class="w-2 h-2 bg-blue-500 rounded-full">
                                                                                            </div>
                                                                                            {{ $label }}
                                                                                        </a>
                                                                                    </li>
                                                                                @endif
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>

                                                                    <!-- Current Filter Criteria -->
                                                                    @if (count($filter_criteria) > 0)
                                                                        <div class="space-y-3">
                                                                            @foreach ($filter_criteria as $field => $value)
                                                                                <div
                                                                                    class="bg-gradient-to-r from-gray-100 to-gray-200 border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
                                                                                    <div class="p-4">
                                                                                        <div
                                                                                            class="flex items-center justify-between mb-3">
                                                                                            <h5
                                                                                                class="font-semibold text-sm text-black flex items-center">
                                                                                                <div
                                                                                                    class="w-3 h-3 bg-black rounded-full mr-2">
                                                                                                </div>
                                                                                                {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                                                            </h5>
                                                                                            <button type="button"
                                                                                                wire:click="removeFilterCriterion('{{ $field }}')"
                                                                                                class="btn btn-ghost btn-xs text-red-500 hover:bg-gray-100 transition-colors rounded-full">
                                                                                                <svg class="w-4 h-4"
                                                                                                    fill="none"
                                                                                                    stroke="currentColor"
                                                                                                    viewBox="0 0 24 24">
                                                                                                    <path
                                                                                                        stroke-linecap="round"
                                                                                                        stroke-linejoin="round"
                                                                                                        stroke-width="2"
                                                                                                        d="M6 18L18 6M6 6l12 12">
                                                                                                    </path>
                                                                                                </svg>
                                                                                            </button>
                                                                                        </div>
                                                                                        <path stroke-linecap="round"
                                                                                            stroke-linejoin="round"
                                                                                            stroke-width="2"
                                                                                            d="M6 18L18 6M6 6l12 12">
                                                                                        </path>
                                                                                        </svg>
                                                                                        </button>
                                                                                    </div>

                                                                                    @if ($field === 'gender')
                                                                                        <select
                                                                                            wire:model.live="filter_criteria.{{ $field }}"
                                                                                            class="select select-bordered select-sm w-full">
                                                                                            <option value="">
                                                                                                Select Gender</option>
                                                                                            @if (isset($organizationFieldOptions['gender']) && count($organizationFieldOptions['gender']) > 0)
                                                                                                @foreach ($organizationFieldOptions['gender'] as $option)
                                                                                                    <option
                                                                                                        value="{{ $option }}">
                                                                                                        {{ ucfirst($option) }}
                                                                                                    </option>
                                                                                                @endforeach
                                                                                            @else
                                                                                                <option value="male">
                                                                                                    Male</option>
                                                                                                <option value="female">
                                                                                                    Female</option>
                                                                                                <option value="other">
                                                                                                    Other</option>
                                                                                            @endif
                                                                                        </select>
                                                                                    @elseif($field === 'status')
                                                                                        <select
                                                                                            wire:model.live="filter_criteria.{{ $field }}"
                                                                                            class="select select-bordered select-sm w-full">
                                                                                            <option value="">
                                                                                                Select Status</option>
                                                                                            @if (isset($organizationFieldOptions['status']) && count($organizationFieldOptions['status']) > 0)
                                                                                                @foreach ($organizationFieldOptions['status'] as $option)
                                                                                                    <option
                                                                                                        value="{{ $option }}">
                                                                                                        {{ ucfirst($option) }}
                                                                                                    </option>
                                                                                                @endforeach
                                                                                            @else
                                                                                                <option value="active">
                                                                                                    Active</option>
                                                                                                <option
                                                                                                    value="inactive">
                                                                                                    Inactive</option>
                                                                                                <option
                                                                                                    value="pending">
                                                                                                    Pending</option>
                                                                                            @endif
                                                                                        </select>
                                                                                    @elseif(in_array($field, ['district', 'county', 'subcounty', 'parish', 'village']))
                                                                                        <select
                                                                                            wire:model.live="filter_criteria.{{ $field }}"
                                                                                            class="select select-bordered select-sm w-full">
                                                                                            <option value="">
                                                                                                Select
                                                                                                {{ ucfirst($field) }}
                                                                                            </option>
                                                                                            @if (isset($organizationFieldOptions[$field]) && count($organizationFieldOptions[$field]) > 0)
                                                                                                @foreach ($organizationFieldOptions[$field] as $option)
                                                                                                    <option
                                                                                                        value="{{ $option }}">
                                                                                                        {{ $option }}
                                                                                                    </option>
                                                                                                @endforeach
                                                                                            @else
                                                                                                <option disabled>No
                                                                                                    {{ $field }}s
                                                                                                    found
                                                                                                </option>
                                                                                            @endif
                                                                                        </select>
                                                                                    @elseif($field === 'age_range')
                                                                                        <input type="text"
                                                                                            wire:model.live="filter_criteria.{{ $field }}"
                                                                                            class="input input-bordered input-sm w-full"
                                                                                            placeholder="e.g., 18-25">
                                                                                        <div class="label">
                                                                                            <span
                                                                                                class="label-text-alt text-info">Format:
                                                                                                min-max
                                                                                                (e.g., 18-25)
                                                                                            </span>
                                                                                        </div>
                                                                                    @else
                                                                                        <input type="text"
                                                                                            wire:model.live="filter_criteria.{{ $field }}"
                                                                                            class="input input-bordered input-sm w-full"
                                                                                            placeholder="Enter {{ strtolower($availableFilters[$field] ?? $field) }}">
                                                                                    @endif
                                                                                </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <div
                                                                    class="text-center py-6 border-2 border-dashed border-base-300 rounded-lg bg-base-50">
                                                                    <svg class="w-8 h-8 mx-auto text-base-content/40 mb-2"
                                                                        fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                                                        </path>
                                                                    </svg>
                                                                    <p class="text-base-content/60 text-sm">No filter
                                                                        criteria added yet</p>
                                                                    <p class="text-base-content/40 text-xs">Add
                                                                        criteria above to build your
                                                                        filter</p>
                                                                </div>
                    @endif

                    @error('filter_criteria')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Preview Section -->
                @if ($previewCount >= 0)
                    <div
                        class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-green-800">Filter Preview</h4>
                                <p class="text-sm text-green-700">
                                    Matches <strong class="text-lg">{{ number_format($previewCount) }}</strong>
                                    person(s) in your organization
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Actions -->
                <div class="flex gap-3 pt-6 border-t border-gray-200">
                    <button type="button" wire:click="resetForm"
                        class="btn btn-outline btn-gray flex-1 hover:bg-gray-50 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Clear Form
                    </button>
                    <button type="submit"
                        class="btn flex-1 border-0 text-white transition-all duration-200" style="background:#982B55;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Profile
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
</div>

<!-- Table -->
<div class="bg-white shadow-lg rounded-b-xl border-l border-r border-b border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Profile
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                </path>
                            </svg>
                            Filter Criteria
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Status
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center justify-end">
                            <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                </path>
                            </svg>
                            Actions
                        </div>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($profiles as $profile)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Profile Info -->
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-black" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $profile->name }}</div>
                                    @if ($profile->description)
                                        <div class="text-xs text-gray-500">
                                            {{ Str::limit($profile->description, 30) }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400">by
                                        {{ $profile->user->name }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Filter Criteria -->
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                @if (count($profile->filter_criteria) > 0)
                                    <div class="space-y-1">
                                        @foreach (array_slice($profile->filter_criteria, 0, 2, true) as $field => $value)
                                            @if (!empty($value))
                                                <div class="flex items-center text-xs">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-black mr-2">
                                                        {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                    </span>
                                                    <span class="text-gray-600 truncate max-w-20">
                                                        @if (is_array($value))
                                                            {{ implode(', ', array_slice($value, 0, 1)) }}{{ count($value) > 1 ? '...' : '' }}
                                                        @else
                                                            {{ Str::limit($value, 15) }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        @endforeach
                                        @if (count($profile->filter_criteria) > 2)
                                            <div class="text-xs text-gray-400">
                                                +{{ count($profile->filter_criteria) - 2 }}
                                                more...
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">No criteria</span>
                                @endif
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                @if ($profile->is_active)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Inactive
                                    </span>
                                @endif
                                @if ($profile->is_shared)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-black">
                                        Shared
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Stats -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="space-y-1">
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-gray-400 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                                        </path>
                                    </svg>
                                    <span class="text-xs text-gray-500">Used
                                        {{ $profile->usage_count }} times</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-gray-400 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                    <span
                                        class="text-xs text-gray-500">~{{ number_format($profile->getEstimatedPersonCount()) }}
                                        matches</span>
                                </div>
                            </div>
                        </td>

                        <!-- Created -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $profile->created_at->format('M j, Y') }}</div>
                            <div class="text-xs">{{ $profile->created_at->diffForHumans() }}
                            </div>
                            @if ($profile->last_used_at)
                                <div class="text-xs text-green-600 mt-1">Last used:
                                    {{ $profile->last_used_at->diffForHumans() }}</div>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Preview -->
                                <button wire:click="openPreviewModal({{ $profile->id }})"
                                    class="text-black hover:text-gray-900 p-1 rounded hover:bg-gray-100 transition-colors"
                                    title="Preview matches">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </button>

                                <!-- Duplicate -->
                                <button wire:click="duplicateProfile({{ $profile->id }})"
                                    class="text-black hover:text-gray-900 p-1 rounded hover:bg-gray-100 transition-colors"
                                    title="Duplicate">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>

                                @if ($this->canManageProfile($profile))
                                    <!-- Edit -->
                                    <button wire:click="editProfile({{ $profile->id }})"
                                        class="text-gray-600 hover:text-gray-900 p-1 rounded hover:bg-gray-50 transition-colors"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>

                                    <!-- Toggle Status -->
                                    <button wire:click="toggleProfileStatus({{ $profile->id }})"
                                        class="text-orange-600 hover:text-orange-900 p-1 rounded hover:bg-orange-50 transition-colors"
                                        title="{{ $profile->is_active ? 'Deactivate' : 'Activate' }}">
                                        @if ($profile->is_active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                                </path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M16 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                </path>
                                            </svg>
                                        @endif
                                    </button>

                                    <!-- Delete -->
                                    <button wire:click="openDeleteModal({{ $profile->id }})"
                                        class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if ($profiles->hasPages())
    <div class="mt-4">
        {{ $profiles->links() }}
    </div>
@endif
@else
<!-- Empty State -->
<div class="text-center py-16 bg-white rounded-xl border border-gray-200 shadow-lg">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-200 rounded-full mb-6">
        <svg class="w-10 h-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z">
            </path>
        </svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-2">No filter profiles yet</h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">Create your first filter profile to start organizing your
        communication targeting. Use the form on the right to get started.</p>
    <div class="inline-flex items-center px-4 py-2 bg-gray-200 text-black rounded-full text-sm font-medium">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6">
            </path>
        </svg>
        Start by creating a filter profile
    </div>
</div>
@endif
</div>

<!-- Right Column - Create/Edit Form -->

</div>
</div>
</svg>
</div>
</div>

</div>
</div>

</div>
</div>
</div>



<!-- Delete Confirmation Modal (keep for deletion confirmations) -->

@if ($showDeleteModal)
    <dialog class="modal" open>
        <div class="modal-box relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg text-error flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    Delete Filter Profile
                </h3>
                <button type="button" wire:click="closeDeleteModal"
                    class="btn btn-ghost btn-sm btn-circle absolute top-4 right-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-red-800 mb-1">This action cannot be undone</h4>
                        <p class="text-red-700 text-sm">
                            Are you sure you want to delete this filter profile? All associated data and settings will
                            be permanently removed.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" wire:click="closeDeleteModal" class="btn btn-ghost">
                    Cancel
                </button>
                <button type="button" wire:click="deleteProfile" class="btn btn-error gap-2"
                    wire:loading.attr="disabled" wire:target="deleteProfile">

                    <!-- Loading spinner -->
                    <svg class="w-4 h-4 animate-spin" wire:loading wire:target="deleteProfile" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>

                    <!-- Delete icon (when not loading) -->
                    <svg class="w-4 h-4" wire:loading.remove wire:target="deleteProfile" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>

                    <!-- Button text -->
                    <span wire:loading.remove wire:target="deleteProfile">Delete Profile</span>
                    <span wire:loading wire:target="deleteProfile">Deleting...</span>
                </button>
            </div>
        </div>
        <!-- Backdrop to close modal when clicking outside -->
        <div class="modal-backdrop" wire:click="closeDeleteModal"></div>
    </dialog>
@endif

<!-- Preview Modal (keep for viewing details) -->
@if ($showPreviewModal)
    <dialog class="modal" open>
        <div class="modal-box max-w-2xl relative">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                    Preview: {{ $name }}
                </h3>
                <form method="dialog">
                    <button type="button" wire:click="closeModals"
                        class="btn btn-ghost btn-sm btn-circle absolute top-4 right-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="bg-gray-100 border border-gray-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-black mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-black">Filter Results</h4>
                        <p class="text-gray-700">This filter matches <strong
                                class="text-xl">{{ number_format($previewCount) }}</strong> person(s) in your
                            organization.</p>
                    </div>
                </div>
            </div>

            <!-- Filter Criteria Details -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                        </path>
                    </svg>
                    Active Filter Criteria
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($filter_criteria as $field => $value)
                        @if (!empty($value))
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <div class="font-medium text-gray-900 text-sm">
                                    {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                </div>
                                <div class="text-gray-700 mt-1">
                                    @if (is_array($value))
                                        @foreach ($value as $item)
                                            <span
                                                class="inline-block bg-white border border-gray-300 rounded px-2 py-1 text-xs mr-1 mb-1">{{ $item }}</span>
                                        @endforeach
                                    @else
                                        <span
                                            class="inline-block bg-white border border-gray-300 rounded px-2 py-1 text-xs">{{ $value }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="closeModals" class="btn btn-black">Close Preview</button>
            </div>
        </div>
    </dialog>
@endif

@if ($showCreateModal || $showEditModal)
    <dialog class="modal" open>
        <div class="modal-box max-w-4xl relative h-[85vh] flex flex-col p-0">
            <!-- Modal Header - Fixed -->
            <div class="flex items-center justify-between p-6 border-b border-base-300 flex-shrink-0">
                <h3 class="font-bold text-xl flex items-center">
                    <svg class="w-6 h-6 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4">
                        </path>
                    </svg>
                    {{ $showCreateModal ? 'Create Filter Profile' : 'Edit Filter Profile' }}

                </h3>

                <!-- Close Button -->
                <form method="dialog">
                    <button type="button" wire:click="closeModals" class="btn btn-ghost btn-sm btn-circle">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Form -->
            <form wire:submit.prevent="saveProfile" class="flex flex-col flex-1 min-h-0">
                <!-- Scrollable Content Area -->
                <div
                    class="flex-1 overflow-y-auto p-3 space-y-3 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <!-- SECTION 1: Basic Information -->
                    <div class="card bg-base-200/50 shadow-sm">
                        <div class="card-body p-3">
                            <h4 class="card-title text-lg mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-info" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Profile Information
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control row">
                                    <div class='col-6'>
                                        <label class="label">
                                            <span class="label-text font-medium">Profile Name <span
                                                    class="text-error">*</span></span>
                                        </label>
                                        <input type="text" wire:model="name" class="input input-bordered w-full"
                                            placeholder="e.g., Students Age 18-25">
                                        @error('name')
                                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                                        @enderror

                                    </div>
                                    <div class='col-6'>
                                        <label class="label">
                                            <span class="label-text font-medium">Description</span>
                                        </label>
                                        <textarea wire:model="description" class="textarea textarea-bordered" rows="3"
                                            placeholder="Optional description to help you remember what this filter is for"></textarea>
                                        @error('description')
                                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">Sharing Options</span>
                                    </label>
                                    <label class="label cursor-pointer justify-start gap-3">
                                        <input type="checkbox" wire:model="is_shared" class="toggle toggle-primary">
                                        <span class="label-text">Share with organization</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-control mt-4">

                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Filter Criteria -->
                    <div class="card bg-base-200/50 shadow-sm">
                        <div class="card-body p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="card-title text-lg flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-warning" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                        </path>
                                    </svg>
                                    Filter Criteria <span class="text-error">*</span>
                                </h4>
                                <div class="dropdown dropdown-end">
                                    <button type="button" tabindex="0" class="btn btn-sm gap-2 border-0 text-white" style="background:#982B55;">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Filter
                                    </button>
                                    <ul tabindex="0"
                                        class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
                                        @foreach ($availableFilters as $key => $label)
                                            @if (!array_key_exists($key, $filter_criteria))
                                                <li><a wire:click="addFilterCriterion('{{ $key }}')"
                                                        class="gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                                                            </path>
                                                        </svg>
                                                        {{ $label }}
                                                    </a></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            @if (count($filter_criteria) > 0)
                                <div class="space-y-4">
                                    @foreach ($filter_criteria as $field => $value)
                                        <div class="card bg-base-100 border border-base-300 shadow-sm">
                                            <div class="card-body p-2">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h5 class="font-semibold text-sm text-primary">
                                                        {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                    </h5>
                                                    <button type="button"
                                                        wire:click="removeFilterCriterion('{{ $field }}')"
                                                        class="btn btn-ghost btn-xs text-error hover:bg-error hover:text-error-content">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>

                                                @if ($field === 'gender')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select Gender</option>
                                                        @if (isset($organizationFieldOptions['gender']) && count($organizationFieldOptions['gender']) > 0)
                                                            @foreach ($organizationFieldOptions['gender'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ ucfirst($option) }}</option>
                                                            @endforeach
                                                        @else
                                                            <option value="male">Male</option>
                                                            <option value="female">Female</option>
                                                            <option value="other">Other</option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'status')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select Status</option>
                                                        @if (isset($organizationFieldOptions['status']) && count($organizationFieldOptions['status']) > 0)
                                                            @foreach ($organizationFieldOptions['status'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ ucfirst($option) }}</option>
                                                            @endforeach
                                                        @else
                                                            <option value="active">Active</option>
                                                            <option value="inactive">Inactive</option>
                                                            <option value="pending">Pending</option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'district')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select District</option>
                                                        @if (isset($organizationFieldOptions['district']) && count($organizationFieldOptions['district']) > 0)
                                                            @foreach ($organizationFieldOptions['district'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ $option }}</option>
                                                            @endforeach
                                                        @else
                                                            <option disabled>No districts found in your organization
                                                            </option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'county')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select County</option>
                                                        @if (isset($organizationFieldOptions['county']) && count($organizationFieldOptions['county']) > 0)
                                                            @foreach ($organizationFieldOptions['county'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ $option }}</option>
                                                            @endforeach
                                                        @else
                                                            <option disabled>No counties found in your organization
                                                            </option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'subcounty')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select Subcounty</option>
                                                        @if (isset($organizationFieldOptions['subcounty']) && count($organizationFieldOptions['subcounty']) > 0)
                                                            @foreach ($organizationFieldOptions['subcounty'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ $option }}</option>
                                                            @endforeach
                                                        @else
                                                            <option disabled>No subcounties found in your organization
                                                            </option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'parish')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select Parish</option>
                                                        @if (isset($organizationFieldOptions['parish']) && count($organizationFieldOptions['parish']) > 0)
                                                            @foreach ($organizationFieldOptions['parish'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ $option }}</option>
                                                            @endforeach
                                                        @else
                                                            <option disabled>No parishes found in your organization
                                                            </option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'village')
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select Village</option>
                                                        @if (isset($organizationFieldOptions['village']) && count($organizationFieldOptions['village']) > 0)
                                                            @foreach ($organizationFieldOptions['village'] as $option)
                                                                <option value="{{ $option }}">
                                                                    {{ $option }}</option>
                                                            @endforeach
                                                        @else
                                                            <option disabled>No villages found in your organization
                                                            </option>
                                                        @endif
                                                    </select>
                                                @elseif($field === 'age_range')
                                                    <input type="text"
                                                        wire:model.live="filter_criteria.{{ $field }}"
                                                        class="input input-bordered input-sm w-full"
                                                        placeholder="e.g., 18-25">
                                                    <div class="label">
                                                        <span class="label-text-alt text-info">Format: min-max
                                                            (e.g., 18-25)
                                                        </span>
                                                    </div>
                                                @elseif(isset($organizationFieldOptions[$field]) &&
                                                        is_array($organizationFieldOptions[$field]) &&
                                                        count($organizationFieldOptions[$field]) > 0)
                                                    <select wire:model.live="filter_criteria.{{ $field }}"
                                                        class="select select-bordered select-sm w-full">
                                                        <option value="">Select
                                                            {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                        </option>
                                                        @foreach ($organizationFieldOptions[$field] as $option)
                                                            <option value="{{ $option }}">{{ $option }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="text"
                                                        wire:model.live="filter_criteria.{{ $field }}"
                                                        class="input input-bordered input-sm w-full"
                                                        placeholder="Enter {{ strtolower($availableFilters[$field] ?? $field) }}">
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div
                                    class="text-center py-6 border-2 border-dashed border-base-300 rounded-lg bg-base-100">
                                    <svg class="w-6 h-6 mx-auto text-base-content/40 mb-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                                        </path>
                                    </svg>
                                    <p class="text-base-content/60 mb-2">No filter criteria added yet</p>
                                    <p class="text-base-content/40 text-sm">Click "Add Filter" above to start
                                        building your filter</p>
                                </div>
                            @endif

                            @error('filter_criteria')
                                <span class="text-error text-sm mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SECTION 3: Preview & Summary -->
                    @if ($previewCount >= 0)
                        <div class="card bg-gradient-to-r from-success/10 to-info/10 border border-success/20">
                            <div class="card-body p-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-success-content">Filter Preview</h4>
                                        <p class="text-success-content/80">This filter will match approximately <strong
                                                class="text-lg">{{ number_format($previewCount) }}</strong> person(s)
                                            in your organization.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Modal Footer - Always Visible -->
                <div
                    class="flex justify-between items-center px-6 py-4 border-t border-base-300 bg-base-100 flex-shrink-0">
                    <button type="button" wire:click="closeModals" class="btn btn-ghost">
                        Cancel
                    </button>

                    <div class="flex gap-3">
                        @if ($showCreateModal)
                            <button type="button" wire:click="saveProfileAndSendMessage"
                                class="btn btn-success gap-2" wire:loading.attr="disabled"
                                wire:target="saveProfileAndSendMessage">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    wire:loading.remove wire:target="saveProfileAndSendMessage">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <svg class="w-4 h-4 animate-spin" wire:loading wire:target="saveProfileAndSendMessage"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span wire:loading.remove wire:target="saveProfileAndSendMessage">Create & Send
                                    Message</span>
                                <span wire:loading wire:target="saveProfileAndSendMessage">Creating...</span>
                                <div class="badge badge-success-content badge-sm" wire:loading.remove
                                    wire:target="saveProfileAndSendMessage">Quick Start</div>
                            </button>
                        @endif

                        <button type="submit" class="btn gap-2 border-0 text-white" style="background:#982B55;"
                            wire:loading.attr="disabled" wire:target="saveProfile">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                wire:loading.remove wire:target="saveProfile">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <svg class="w-4 h-4 animate-spin" wire:loading wire:target="saveProfile" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span wire:loading.remove
                                wire:target="saveProfile">{{ $showCreateModal ? 'Create Profile' : 'Update Profile' }}</span>
                            <span wire:loading
                                wire:target="saveProfile">{{ $showCreateModal ? 'Creating...' : 'Updating...' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </dialog>
@endif

<!-- Preview Modal -->
@if ($showPreviewModal)
    <dialog class="modal" open>
        <div class="modal-box max-w-2xl relative">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 616 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                    Preview: {{ $name }}
                </h3>
                <form method="dialog">
                    <button type="button" wire:click="closeModals"
                        class="btn btn-ghost btn-sm btn-circle absolute top-4 right-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="bg-gray-100 border border-gray-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-black mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-black">Filter Results</h4>
                        <p class="text-gray-700">This filter matches <strong
                                class="text-xl">{{ number_format($previewCount) }}</strong> person(s) in your
                            organization.</p>
                    </div>
                </div>
            </div>

            <!-- Filter Criteria Details -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z">
                        </path>
                    </svg>
                    Active Filter Criteria
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($filter_criteria as $field => $value)
                        @if (!empty($value))
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <div class="font-medium text-gray-900 text-sm">
                                    {{ $availableFilters[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                </div>
                                <div class="text-gray-700 mt-1">
                                    @if (is_array($value))
                                        @foreach ($value as $item)
                                            <span
                                                class="inline-block bg-white border border-gray-300 rounded px-2 py-1 text-xs mr-1 mb-1">{{ $item }}</span>
                                        @endforeach
                                    @else
                                        <span
                                            class="inline-block bg-white border border-gray-300 rounded px-2 py-1 text-xs">{{ $value }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="closeModals" class="btn btn-ghost">Close Preview</button>
            </div>
        </div>
    </dialog>
@endif
