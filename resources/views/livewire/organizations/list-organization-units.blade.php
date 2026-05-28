<div class="min-h-full py-6 px-4 md:px-8">

    {{-- Page secondary header (hoisted to layout header slot) --}}
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Projects Management</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Organization Units</h1>
            </div>
            @can('create-units')
                <a href="{{ route('organization-units.create') }}"
                   class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Organization Unit
                </a>
            @endcan
        </div>
    </x-slot>

    {{-- Main content --}}
    <div class="w-full space-y-4">

        {{-- Search & filter bar --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex flex-1 gap-2">
                    <input type="text"
                           wire:model.lazy="search"
                           placeholder="Search by name or code…"
                           class="input input-bordered flex-1" />
                    <button class="btn border-0 text-white" style="background:#982B55;"
                            wire:click="updateUnits" type="button">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                        <span class="hidden sm:inline ml-1">Search</span>
                    </button>
                    <button class="btn btn-ghost" type="button"
                            wire:click="$set('search', ''); updateUnits();"
                            title="Clear search">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <select wire:model="statusFilter" class="select select-bordered w-full sm:w-44">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            {{-- Loading indicator --}}
            <div wire:loading.flex wire:target="search, statusFilter, updateUnits"
                 class="items-center gap-2 mt-3 text-sm text-rose-600">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Loading results…</span>
            </div>
        </div>

        {{-- Unit tree --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @if(count($unitTree) > 0)
                <ul class="p-4 space-y-1">
                    @foreach($unitTree as $unit)
                        @include('livewire.organizations.partials.unit-tree', [
                            'unit'        => $unit,
                            'level'       => 0,
                            'units'       => $units,
                            'movingUnitId'=> $movingUnitId,
                        ])
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No organization units found</p>
                    <p class="text-sm text-gray-400 mt-1">
                        @if($search || $statusFilter)
                            Try adjusting your search or filter.
                        @else
                            Get started by creating the first unit.
                        @endif
                    </p>
                    @can('create-units')
                        @if(!$search && !$statusFilter)
                            <a href="{{ route('organization-units.create') }}"
                               class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">
                                Create First Unit
                            </a>
                        @endif
                    @endcan
                </div>
            @endif
        </div>

    </div>

    {{-- Details drawer --}}
    <div class="drawer drawer-end">
        <input id="unit-details-drawer" type="checkbox" class="drawer-toggle" @if($selectedUnit) checked @endif />
        <div class="drawer-content"></div>
        <div class="drawer-side z-50">
            <label for="unit-details-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="menu bg-base-200 min-h-full w-96 p-4">
                @if($selectedUnit)
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">Unit Details: {{ $selectedUnit->name }}</h3>
                        <label for="unit-details-drawer" class="btn btn-sm btn-ghost">&times;</label>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 mb-4">
                        <div class="flex flex-col gap-2 mb-4">
                            <div class="text-xs text-gray-500">Organization:
                                <span class="font-semibold text-gray-800">
                                    {{ optional($selectedUnit->Organization)->name ?? (optional(\App\Models\Organization::find($selectedUnit->organization_id))->name ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="bg-gray-200 rounded-full w-14 h-14 flex items-center justify-center text-2xl font-bold text-rose-600">
                                    {{ strtoupper(substr($selectedUnit->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-lg font-bold">{{ $selectedUnit->name }}</div>
                                    <div class="text-xs text-gray-500">Code: <span class="font-mono">{{ $selectedUnit->code }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-gray-500">Status</div>
                            <div class="font-semibold {{ $selectedUnit->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $selectedUnit->is_active ? 'Active' : 'Inactive' }}</div>
                            <div class="text-gray-500">Type</div>
                            <div>{{ $selectedUnit->unit_type ?? 'N/A' }}</div>
                            <div class="text-gray-500">Department</div>
                            <div>{{ $selectedUnit->department ?? 'N/A' }}</div>
                            <div class="text-gray-500">Community</div>
                            <div>{{ $selectedUnit->community ?? 'N/A' }}</div>
                            <div class="text-gray-500">Ministry Committee</div>
                            <div>{{ $selectedUnit->ministry_committee ?? 'N/A' }}</div>
                            <div class="text-gray-500">Administrative Office</div>
                            <div>{{ $selectedUnit->administrative_office ?? 'N/A' }}</div>
                            <div class="text-gray-500">Head</div>
                            <div>{{ $selectedUnit->unit_head ? (optional(\App\Models\Person::find($selectedUnit->unit_head))->full_name ?? 'N/A') : 'N/A' }}</div>
                            <div class="text-gray-500">Assistant Leader</div>
                            <div>{{ $selectedUnit->assistant_leader ? (optional(\App\Models\Person::find($selectedUnit->assistant_leader))->full_name ?? 'N/A') : 'N/A' }}</div>
                            <div class="text-gray-500">Contact Email</div>
                            <div>{{ $selectedUnit->official_email ?? 'N/A' }}</div>
                            <div class="text-gray-500">Phone</div>
                            <div>{{ $selectedUnit->phone_contact ?? 'N/A' }}</div>
                            <div class="text-gray-500">Location</div>
                            <div>{{ $selectedUnit->physical_location ?? 'N/A' }}</div>
                            <div class="text-gray-500">Website</div>
                            <div>{{ $selectedUnit->website ?? 'N/A' }}</div>
                        </div>
                        <div class="border-t my-4"></div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-gray-500">Mission</div>
                            <div>{{ $selectedUnit->mission ?? 'N/A' }}</div>
                            <div class="text-gray-500">Objectives</div>
                            <div>{{ $selectedUnit->objectives ?? 'N/A' }}</div>
                            <div class="text-gray-500">Activities</div>
                            <div>{{ $selectedUnit->activities ?? 'N/A' }}</div>
                            <div class="text-gray-500">Target Audience</div>
                            <div>{{ $selectedUnit->target_audience ?? 'N/A' }}</div>
                        </div>
                        <div class="border-t my-4"></div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-gray-500">Membership Type</div>
                            <div>{{ $selectedUnit->membership_type ?? 'N/A' }}</div>
                            <div class="text-gray-500">Eligibility</div>
                            <div>{{ $selectedUnit->membership_eligibility ?? 'N/A' }}</div>
                            <div class="text-gray-500">Capacity</div>
                            <div>{{ $selectedUnit->membership_capacity ?? 'N/A' }}</div>
                            <div class="text-gray-500">Join Requests</div>
                            <div>{{ $selectedUnit->join_requests_enabled ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="border-t my-4"></div>
                        <livewire:organizations.manage-unit-members
                            :unitId="$selectedUnit->id"
                            :key="'members-'.$selectedUnit->id" />
                        <div class="border-t my-4"></div>
                        <div class="flex gap-2 items-center mt-2">
                            <livewire:organizations.apply-to-unit :unitId="$selectedUnit->id" />
                            <button class="btn btn-outline btn-sm"
                                    wire:click="exportUnitMembers({{ $selectedUnit->id }})">
                                Export Members
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('open-unit-details-drawer', () => {
            const drawer = document.getElementById('unit-details-drawer');
            if (drawer) drawer.checked = true;
        });
    </script>

</div>
