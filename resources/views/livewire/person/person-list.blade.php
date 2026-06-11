<div wire:key="person-list-component">

    {{-- Page Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4 sticky top-0 z-10">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">People</div>
            <h2 class="text-sm font-semibold text-gray-800">All Persons</h2>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @can('export-org-persons')
                <a href="{{ route('persons.export') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </a>
            @endcan
            @can('import-org-persons')
                <a href="{{ route('persons.import') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    Import
                </a>
            @endcan
            <a href="{{ route('persons.search') }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
                style="background:#982B55;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search Person
            </a>
            <a href="{{ route('persons.create') }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
                style="background:#982B55;">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd"/>
                </svg>
                Add Person
            </a>
        </div>
    </div>

    <div class="p-4">
        <x-alerts />

        {{-- Search toolbar --}}
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                    class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                    style="--tw-ring-color: #982B55;"
                    placeholder="Search by name, ID, phone..."
                    wire:model.live.debounce.500ms="filters.search"
                    autocomplete="off"/>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <div wire:loading wire:target="filters,dynamicFilters,updatedFilters,updatedDynamicFilters"
                    class="flex items-center gap-1.5 text-gray-500">
                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    Searching...
                </div>
                <div wire:loading.remove wire:target="filters,dynamicFilters" class="text-gray-400">
                    {{ $persons->total() }} {{ Str::plural('person', $persons->total()) }}
                </div>
            </div>
        </div>

        {{-- Persons Table --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden relative">
            {{-- Loading Overlay --}}
            <div wire:loading wire:target="filters,dynamicFilters,updatedFilters,updatedDynamicFilters"
                class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-20">
                <div class="text-center">
                    <svg class="animate-spin h-7 w-7 mx-auto mb-2" style="color:#982B55;" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <div class="text-sm text-gray-600 font-medium">Searching...</div>
                </div>
            </div>

            @if ($persons->count() > 0)
                <div class="overflow-x-auto" wire:key="persons-table">
                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider w-8">#</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Person</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Affiliations</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($persons as $index => $person)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    {{-- Row number --}}
                                    <td class="px-3 py-2.5 text-gray-400 font-mono">
                                        {{ $persons->firstItem() + $index }}
                                    </td>

                                    {{-- Person Info --}}
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <div class="relative flex-shrink-0">
                                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                                     style="background:#982B55;">
                                                    {{ strtoupper(substr($person->given_name, 0, 1)) }}{{ strtoupper(substr($person->family_name, 0, 1)) }}
                                                </div>
                                                @php
                                                    $completeness = 0;
                                                    if ($person->phones->count() > 0) $completeness += 25;
                                                    if ($person->emailAddresses->count() > 0) $completeness += 25;
                                                    if ($person->affiliations->where('status', 'active')->count() > 0) $completeness += 25;
                                                    if (!empty($person->classification)) $completeness += 25;
                                                    $dotColor = $completeness >= 75 ? 'bg-green-500' : ($completeness >= 50 ? 'bg-yellow-500' : 'bg-red-400');
                                                @endphp
                                                <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white {{ $dotColor }}"
                                                     title="Profile {{ $completeness }}% complete"></div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium text-gray-900 truncate">{{ $person->full_name }}</div>
                                                <div class="text-gray-400 font-mono">{{ $person->person_id }}</div>
                                                @if ($person->date_of_birth)
                                                    @php
                                                        $dob = $person->date_of_birth;
                                                        if (is_string($dob)) {
                                                            try { $dob = \Carbon\Carbon::parse($dob); } catch (Exception $e) { $dob = null; }
                                                        }
                                                    @endphp
                                                    <div class="text-gray-400">{{ $dob ? $dob->format('M j, Y') : '' }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Contact Info --}}
                                    <td class="px-3 py-2.5">
                                        <div class="space-y-0.5">
                                            @if ($person->phones->count() > 0)
                                                <div class="flex items-center gap-1 text-gray-700">
                                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    {{ $person->phones->pluck('number')->join(', ') }}
                                                </div>
                                            @else
                                                <div class="text-gray-400 italic">No phone</div>
                                            @endif
                                            @if ($person->emailAddresses->count() > 0)
                                                <div class="flex items-center gap-1 text-gray-700 truncate max-w-[180px]">
                                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span class="truncate">{{ $person->emailAddresses->pluck('email')->join(', ') }}</span>
                                                </div>
                                            @else
                                                <div class="text-gray-400 italic">No email</div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Affiliations --}}
                                    <td class="px-3 py-2.5">
                                        @php
                                            $activeAffiliations = $person->affiliations->where('status', 'active');
                                            $totalAffiliations = $person->affiliations->count();
                                        @endphp
                                        @forelse($activeAffiliations->take(2) as $affiliation)
                                            <div class="flex items-start gap-1.5 mb-1">
                                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mt-1.5 flex-shrink-0"></div>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-gray-800 truncate max-w-[160px]">
                                                        {{ $affiliation->Organization->legal_name ?? 'Not Provided' }}
                                                    </div>
                                                    @if ($affiliation->role_title)
                                                        <div class="text-gray-400 truncate max-w-[160px]">{{ $affiliation->role_title }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="flex items-center gap-1.5 text-gray-400 italic">
                                                <div class="w-1.5 h-1.5 bg-gray-300 rounded-full flex-shrink-0"></div>
                                                No affiliations
                                            </div>
                                        @endforelse
                                        @if ($totalAffiliations > $activeAffiliations->count())
                                            <div class="text-orange-500 mt-0.5">
                                                +{{ $totalAffiliations - $activeAffiliations->count() }} inactive
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Location --}}
                                    <td class="px-3 py-2.5">
                                        @if ($person->country || $person->city || $person->district)
                                            <div class="space-y-0.5">
                                                @if ($person->city)
                                                    <div class="font-medium text-gray-800">{{ $person->city }}</div>
                                                @endif
                                                @if ($person->district)
                                                    <div class="text-gray-500">{{ $person->district }}</div>
                                                @endif
                                                @if ($person->country)
                                                    <div class="text-gray-400">{{ $person->country }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-gray-400 italic">No location</div>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-3 py-2.5 text-right">
                                        <div x-data="{ open: false }" class="relative inline-block" @click.away="open = false">
                                            <button @click="open = !open" type="button"
                                                class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors focus:outline-none">
                                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <circle cx="10" cy="4" r="1.5"/>
                                                    <circle cx="10" cy="10" r="1.5"/>
                                                    <circle cx="10" cy="16" r="1.5"/>
                                                </svg>
                                            </button>
                                            <div x-show="open"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-20 py-1"
                                                style="display:none;">
                                                <a href="{{ route('persons.show', ['id' => $person->id]) }}"
                                                    class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View Profile
                                                </a>
                                                <button wire:click="editPerson({{ $person->id }})" @click="open = false" type="button"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 10-4-4l-8 8v3z"/>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button type="button" @click="open = false"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Add Affiliation
                                                </button>
                                                @can('delete-persons')
                                                    <div class="border-t border-gray-100 mt-1 pt-1">
                                                        <button wire:click="confirmDelete({{ $person->id }})" @click="open = false" type="button"
                                                            class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($persons->hasPages())
                    <div class="bg-gray-50 px-4 py-3 border-t border-gray-100" wire:key="pagination">
                        {{ $persons->links() }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="text-center py-16">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">No persons found</h3>
                    <p class="text-xs text-gray-500 mb-6">
                        @php
                            $hasActiveFilters = collect($filters)->filter(function ($value, $key) {
                                if ($key === 'date_range') return !empty($value['start']) || !empty($value['end']);
                                return !empty($value);
                            })->count() + collect($dynamicFilters)->filter()->count() > 0;
                        @endphp
                        {{ $hasActiveFilters ? 'Try adjusting your search criteria.' : 'Get started by adding a new person.' }}
                    </p>
                    <a href="{{ route('persons.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
                        style="background:#982B55;">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Add New Person
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="cancelDelete"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Person</h3>
                            <p class="text-sm text-gray-500">
                                This action cannot be undone. All associated phone numbers, email addresses, and affiliations will be permanently removed.
                            </p>
                            @if ($personToDelete)
                                <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                    <p class="text-sm font-semibold text-gray-900">{{ $personToDelete->full_name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">ID: {{ $personToDelete->person_id }}</p>
                                    @if ($personToDelete->phones->count() > 0 || $personToDelete->emailAddresses->count() > 0 || $personToDelete->affiliations->count() > 0)
                                        <ul class="mt-2 text-xs text-red-700 space-y-0.5 list-disc list-inside">
                                            @if ($personToDelete->phones->count() > 0)
                                                <li>{{ $personToDelete->phones->count() }} phone number(s)</li>
                                            @endif
                                            @if ($personToDelete->emailAddresses->count() > 0)
                                                <li>{{ $personToDelete->emailAddresses->count() }} email address(es)</li>
                                            @endif
                                            @if ($personToDelete->affiliations->count() > 0)
                                                <li>{{ $personToDelete->affiliations->count() }} affiliation(s)</li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button wire:click="cancelDelete" type="button" wire:loading.attr="disabled" wire:target="deletePerson"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                            Cancel
                        </button>
                        <button wire:click="deletePerson" type="button" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                            <span wire:loading.remove wire:target="deletePerson">Delete</span>
                            <span wire:loading wire:target="deletePerson" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Person Modal --}}
    @if ($showEditModal && $editPersonId)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="cancelEdit"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full z-10">
                    <form wire:submit.prevent="updatePerson">
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Edit Person</h3>
                            <button type="button" wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Given Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="editPersonData.given_name"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                @error('editPersonData.given_name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" wire:model="editPersonData.middle_name"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Family Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="editPersonData.family_name"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                @error('editPersonData.family_name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="editPersonData.date_of_birth"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                @error('editPersonData.date_of_birth') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                <select wire:model="editPersonData.gender"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                @error('editPersonData.gender') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                                <input type="tel" wire:model="editPersonData.phone" placeholder="+256 700 123 456"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                @error('editPersonData.phone') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" wire:model="editPersonData.email" placeholder="name@email.com"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                                @error('editPersonData.email') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">City</label>
                                <input type="text" wire:model="editPersonData.city"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">District</label>
                                <input type="text" wire:model="editPersonData.district"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Country</label>
                                <input type="text" wire:model="editPersonData.country"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Role Type</label>
                                <input type="text" wire:model="editPersonData.role_type"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Role Title</label>
                                <input type="text" wire:model="editPersonData.role_title"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                                <textarea wire:model="editPersonData.address" rows="2" placeholder="Street address"
                                    class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #982B55;"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-end gap-3">
                            <button type="button" wire:click="cancelEdit" wire:loading.attr="disabled" wire:target="updatePerson"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2"
                                style="background:#982B55;">
                                <span wire:loading.remove wire:target="updatePerson">Save Changes</span>
                                <span wire:loading wire:target="updatePerson" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        window.addEventListener('filters-reset', () => {});

        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', ({ el }) => {
                if (window.Alpine) Alpine.initTree(el);
            });
        });
    </script>
</div>
