<div class="min-h-full py-6 px-4 md:px-8">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">People</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Export Persons</h1>
            </div>
            @if(!empty($exportStats) && $exportStats['total_persons'] > 0)
                <span class="text-xs text-gray-500 flex-shrink-0">
                    <span class="font-semibold text-gray-700">{{ number_format($exportStats['total_persons']) }}</span> record(s) matched
                </span>
            @endif
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Alerts --}}
        @if(session()->has('message'))
            <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('message') }}
            </div>
        @endif

        @error('export')
            <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $message }}
            </div>
        @enderror

        {{-- Configuration card --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Export Configuration</h3>
                <p class="text-xs text-gray-500 mt-0.5">Choose format, fields and filters to narrow down your export.</p>
            </div>
            <div class="p-5 space-y-6">

                {{-- Organization (Super Admin) --}}
                @if($isSuperAdmin && !empty($availableOrganizations))
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Target Organization</label>
                        <select wire:model.live="selectedOrganizationId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-transparent">
                            <option value="">All Organizations</option>
                            @foreach($availableOrganizations as $org)
                                <option value="{{ $org['id'] }}">{{ $org['legal_name'] }}</option>
                            @endforeach
                        </select>
                        @error('selectedOrganizationId')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Export Format --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-2">Export Format</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer rounded-lg border p-4 transition-colors
                            {{ $exportFormat === 'xlsx' ? 'border-rose-400 bg-rose-50 ring-1 ring-rose-400' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="exportFormat" value="xlsx" class="sr-only" />
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">Excel (.xlsx)</span>
                                <span class="text-xs text-gray-500 mt-0.5">Rich formatting, styles, multiple sheets</span>
                            </span>
                        </label>
                        <label class="relative flex cursor-pointer rounded-lg border p-4 transition-colors
                            {{ $exportFormat === 'csv' ? 'border-rose-400 bg-rose-50 ring-1 ring-rose-400' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="exportFormat" value="csv" class="sr-only" />
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">CSV (.csv)</span>
                                <span class="text-xs text-gray-500 mt-0.5">Simple format, universal compatibility</span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Field Selection --}}
                @if(!empty($availableFieldOptions))
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-medium text-gray-700">Fields to Export</label>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="selectAllFields"
                                    class="text-xs font-medium hover:underline" style="color:#982B55;">
                                    Select All
                                </button>
                                <span class="text-xs text-gray-300">|</span>
                                <button type="button" wire:click="selectDefaultFields"
                                    class="text-xs font-medium hover:underline" style="color:#982B55;">
                                    Default Only
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                            @foreach($availableFieldOptions as $fieldKey => $field)
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox"
                                        wire:model.live="includeFields"
                                        value="{{ $fieldKey }}"
                                        class="w-3.5 h-3.5 rounded border-gray-300 text-rose-600 focus:ring-rose-300" />
                                    <span class="text-xs text-gray-700 group-hover:text-gray-900">
                                        {{ $field['label'] }}
                                        @if(isset($field['default']) && $field['default'])
                                            <span class="text-rose-500 ml-0.5">•</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('includeFields')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Filters --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-medium text-gray-700">Filters</label>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="toggleAdvancedFilters"
                                class="text-xs font-medium hover:underline" style="color:#982B55;">
                                {{ $showAdvancedFilters ? 'Hide Advanced' : 'Show Advanced' }}
                            </button>
                            @if(!empty($filters))
                                <span class="text-xs text-gray-300">|</span>
                                <button type="button" wire:click="clearFilters"
                                    class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                                    Clear All
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if(isset($availableFilterOptions['role_types']))
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Role Type</label>
                                <select wire:model.live="filters.role_type"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                    <option value="">All Types</option>
                                    @foreach($availableFilterOptions['role_types'] as $type)
                                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Gender</label>
                            <select wire:model.live="filters.gender"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                            <select wire:model.live="filters.status"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    @if($showAdvancedFilters)
                        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Age From</label>
                                <input type="number" wire:model.live="filters.age_from"
                                    min="0" max="120" placeholder="Min age"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300" />
                                @error('filters.age_from')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Age To</label>
                                <input type="number" wire:model.live="filters.age_to"
                                    min="0" max="120" placeholder="Max age"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300" />
                                @error('filters.age_to')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">City</label>
                                <input type="text" wire:model.live="filters.city" placeholder="Filter by city"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">District</label>
                                <input type="text" wire:model.live="filters.district" placeholder="Filter by district"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300" />
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Export Preview Stats --}}
                @if(!empty($exportStats) && $exportStats['total_persons'] > 0)
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <p class="text-xs font-medium text-gray-600 mb-3">Export Preview</p>
                        <div class="flex flex-wrap gap-6 text-sm">
                            <div>
                                <span class="text-xs text-gray-500">Total Records</span>
                                <p class="font-semibold text-gray-800 mt-0.5">{{ number_format($exportStats['total_persons']) }}</p>
                            </div>
                            @if(isset($exportStats['by_gender']['male']))
                                <div>
                                    <span class="text-xs text-gray-500">Male</span>
                                    <p class="font-semibold text-gray-800 mt-0.5">{{ number_format($exportStats['by_gender']['male'] ?? 0) }}</p>
                                </div>
                            @endif
                            @if(isset($exportStats['by_gender']['female']))
                                <div>
                                    <span class="text-xs text-gray-500">Female</span>
                                    <p class="font-semibold text-gray-800 mt-0.5">{{ number_format($exportStats['by_gender']['female'] ?? 0) }}</p>
                                </div>
                            @endif
                            @if(isset($exportStats['estimated_size']))
                                <div>
                                    <span class="text-xs text-gray-500">Est. Size</span>
                                    <p class="font-semibold text-gray-800 mt-0.5">{{ $exportStats['estimated_size'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" wire:click="clearFilters"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                    <button type="button"
                        wire:click="exportPersons"
                        wire:loading.attr="disabled"
                        wire:target="exportPersons"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background:#982B55;">
                        <span wire:loading.remove wire:target="exportPersons" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export {{ strtoupper($exportFormat) }}
                        </span>
                        <span wire:loading wire:target="exportPersons" class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
