<div>
    @if($showRelationships && count($filteredPersonIds) > 0)
        <div class="bg-white rounded-xl shadow-lg mt-6">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-rose-50 to-pink-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-rose-100">
                            <i class="fas fa-project-diagram text-rose-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Related Connections</h3>
                            <p class="text-sm text-gray-600">
                                Showing relationships for {{ count($filteredPersonIds) }} filtered 
                                {{ Str::plural('person', count($filteredPersonIds)) }}
                            </p>
                        </div>
                    </div>

                    {{-- View Mode Switcher --}}
                    <div class="flex items-center space-x-2">
                        <div class="flex bg-white rounded-lg shadow-sm border border-gray-200 p-1">
                            <button wire:click="setViewMode('grid')"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'grid' ? 'bg-rose-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                <i class="fas fa-th-large mr-1"></i>Grid
                            </button>
                            <button wire:click="setViewMode('table')"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'table' ? 'bg-rose-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                <i class="fas fa-table mr-1"></i>Table
                            </button>
                            <button wire:click="setViewMode('network')"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'network' ? 'bg-rose-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                <i class="fas fa-sitemap mr-1"></i>Network
                            </button>
                            <button wire:click="setViewMode('list')"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors {{ $viewMode === 'list' ? 'bg-rose-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                <i class="fas fa-list mr-1"></i>List
                            </button>
                        </div>

                        <button wire:click="exportRelationships"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors shadow-sm">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats Bar --}}
            @if($stats['total_related'] > 0)
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Total Related</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_related'] }}</p>
                                </div>
                                <i class="fas fa-users text-rose-400 text-2xl"></i>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Connections</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $stats['unique_connections'] }}</p>
                                </div>
                                <i class="fas fa-link text-purple-400 text-2xl"></i>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Primary Contacts</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $stats['with_primary_contact'] }}</p>
                                </div>
                                <i class="fas fa-star text-yellow-400 text-2xl"></i>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Relationship Types</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ count($stats['by_type']) }}</p>
                                </div>
                                <i class="fas fa-tags text-green-400 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-white">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900">Filter by Relationship Type</h4>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">Group by:</span>
                        <select wire:model.live="groupBy"
                                class="text-xs border-gray-300 rounded-md focus:ring-rose-400 focus:border-rose-400">
                            <option value="none">No Grouping</option>
                            <option value="relationship_type">Relationship Type</option>
                            <option value="person">Source Person</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach($relationshipTypes as $type => $enabled)
                        <button wire:click="toggleRelationshipType('{{ $type }}')"
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all {{ $enabled ? 'bg-rose-100 text-rose-700 border-2 border-rose-300' : 'bg-gray-100 text-gray-500 border-2 border-gray-200' }}">
                            <i class="fas fa-{{ $enabled ? 'check-circle' : 'circle' }} mr-1.5"></i>
                            {{ ucwords(str_replace('_', ' ', $type)) }}
                            @if(isset($stats['by_type'][$type]))
                                <span class="ml-1.5 px-1.5 py-0.5 bg-white rounded-full text-xs font-semibold">
                                    {{ $stats['by_type'][$type] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Content Area --}}
            <div class="px-6 py-6">
                @if($relatedPersons->isEmpty())
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-friends text-gray-400 text-3xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No Related Connections Found</h4>
                        <p class="text-gray-500">Try enabling more relationship types or adjusting your filters.</p>
                    </div>
                @else
                    {{-- GRID VIEW --}}
                    @if($viewMode === 'grid')
                        @foreach($groupedRelationships as $groupName => $persons)
                            @if($groupBy !== 'none')
                                <div class="mb-6">
                                    <button wire:click="toggleGroup('{{ $groupName }}')"
                                            class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors mb-3">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-{{ in_array($groupName, $expandedGroups) ? 'chevron-down' : 'chevron-right' }} text-gray-400"></i>
                                            <h5 class="text-sm font-semibold text-gray-900">
                                                {{ ucwords(str_replace('_', ' ', $groupName)) }}
                                            </h5>
                                            <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-medium">
                                                {{ $persons->count() }}
                                            </span>
                                        </div>
                                    </button>

                                    @if(in_array($groupName, $expandedGroups))
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 ml-8">
                                            @foreach($persons as $person)
                                                @include('livewire.partials.relationship-card', ['person' => $person])
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    @foreach($persons as $person)
                                        @include('livewire.partials.relationship-card', ['person' => $person])
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endif

                    {{-- TABLE VIEW --}}
                    @if($viewMode === 'table')
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            <input type="checkbox" class="rounded border-gray-300">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Person</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Relationship</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Connected To</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($relatedPersons as $person)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" 
                                                       wire:click="selectRelationship({{ $person->id }})"
                                                       {{ in_array($person->id, $selectedRelationships) ? 'checked' : '' }}
                                                       class="rounded border-gray-300">
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white font-semibold">
                                                            {{ substr($person->first_name, 0, 1) }}{{ substr($person->last_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ $person->full_name }}</div>
                                                        <div class="text-xs text-gray-500">ID: {{ $person->person_id }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @foreach($person->relationships as $rel)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800 mb-1">
                                                        {{ ucwords(str_replace('_', ' ', $rel['type'])) }}
                                                        @if($rel['is_primary'])
                                                            <i class="fas fa-star text-yellow-500 ml-1"></i>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900">
                                                    @foreach($person->relationships as $rel)
                                                        <div class="mb-1">{{ $rel['source_person_name'] }}</div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900">
                                                    @if($person->phones->first())
                                                        <div class="flex items-center text-xs">
                                                            <i class="fas fa-phone text-gray-400 mr-1"></i>
                                                            {{ $person->phones->first()->number }}
                                                        </div>
                                                    @endif
                                                    @if($person->emailAddresses->first())
                                                        <div class="flex items-center text-xs mt-1">
                                                            <i class="fas fa-envelope text-gray-400 mr-1"></i>
                                                            {{ $person->emailAddresses->first()->email }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                    {{ $person->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($person->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('persons.show', $person) }}" 
                                                   class="text-rose-600 hover:text-rose-900 text-sm font-medium">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- NETWORK VIEW --}}
                    @if($viewMode === 'network')
                        <div class="bg-gray-50 rounded-lg p-6">
                            <div id="network-visualization" class="w-full" style="height: 600px;">
                                @include('livewire.partials.network-view', ['persons' => $relatedPersons])
                            </div>
                        </div>
                    @endif

                    {{-- LIST VIEW --}}
                    @if($viewMode === 'list')
                        <div class="space-y-3">
                            @foreach($relatedPersons as $person)
                                @include('livewire.partials.relationship-list-item', ['person' => $person])
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading.delay class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 flex items-center space-x-4">
            <svg class="animate-spin h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-800 text-lg font-medium">Loading relationships...</span>
        </div>
    </div>
</div>
