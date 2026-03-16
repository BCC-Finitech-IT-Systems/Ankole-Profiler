<div class="max-w-4xl mt-2 bg-white p-8 rounded shadow">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Project Units</h2>
        @can('create-units')
            <a href="{{ route('organization-units.create') }}" class="btn btn-primary gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Unit
            </a>
        @endcan
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-4">
        <div class="flex-1 flex gap-2">
            <input type="text" wire:model.lazy="search" placeholder="Search by name or code..." class="input input-bordered w-full" />
            <button class="btn btn-primary" wire:click="updateUnits" type="button">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" /></svg>
                Search
            </button>
            <button class="btn btn-ghost" type="button" wire:click="$set('search', ''); updateUnits();">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="w-48">
            <select wire:model="statusFilter" class="select select-bordered w-full">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>
    <div wire:loading.flex wire:target="search, statusFilter" class="flex items-center gap-2 mb-2 text-blue-600">
        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span>Loading results...</span>
    </div>
    <ul class="ml-2">
        @foreach($unitTree as $unit)
            @include('livewire.organizations.partials.unit-tree', [
                'unit' => $unit,
                'level' => 0,
                'units' => $units,
                'movingUnitId' => $movingUnitId
            ])
        @endforeach
    </ul>
     <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Organization Units') }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">Manage organization unit hierarchy and structures</p>
            </div>
            @can('create-units')
                <div class="flex justify-end mt-2">
                    <a href="{{ route('organization-units.create') }}" class="btn btn-accent gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Organization Unit
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="drawer drawer-end">
        <input id="unit-details-drawer" type="checkbox" class="drawer-toggle" @if($selectedUnit) checked @endif />
        <div class="drawer-content">
            <!-- Page content here (unit tree, etc.) -->
            <!-- The rest of your page content remains here -->
        </div>
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
                                <div class="bg-gray-200 rounded-full w-14 h-14 flex items-center justify-center text-2xl font-bold text-blue-600">
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
                            <div class="text-gray-500">Social Links</div>
                            <div>{{ $selectedUnit->social_links ?? 'N/A' }}</div>
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
                            <div class="text-gray-500">Category</div>
                            <div>{{ $selectedUnit->unit_category ?? 'N/A' }}</div>
                            <div class="text-gray-500">Faith Based</div>
                            <div>{{ $selectedUnit->faith_based ? 'Yes' : 'No' }}</div>
                            <div class="text-gray-500">Socio Economic</div>
                            <div>{{ $selectedUnit->socio_economic ? 'Yes' : 'No' }}</div>
                            <div class="text-gray-500">Support Services</div>
                            <div>{{ $selectedUnit->support_services ? 'Yes' : 'No' }}</div>
                            <div class="text-gray-500">Operational Status</div>
                            <div>{{ $selectedUnit->operational_status ?? 'N/A' }}</div>
                            <div class="text-gray-500">Date Established</div>
                            <div>{{ $selectedUnit->date_established ?? 'N/A' }}</div>
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
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-gray-500">Recurring Programs</div>
                            <div>{{ $selectedUnit->recurring_programs ?? 'N/A' }}</div>
                            <div class="text-gray-500">Event Schedule</div>
                            <div>{{ $selectedUnit->event_schedule ?? 'N/A' }}</div>
                            <div class="text-gray-500">Promotion Permissions</div>
                            <div>{{ $selectedUnit->promotion_permissions ?? 'N/A' }}</div>
                            <div class="text-gray-500">Resource Access</div>
                            <div>{{ $selectedUnit->resource_access_requirements ?? 'N/A' }}</div>
                        </div>
                        <div class="border-t my-4"></div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-gray-500">Showcase Permissions</div>
                            <div>{{ $selectedUnit->showcase_permissions ?? 'N/A' }}</div>
                            <div class="text-gray-500">Product Categories</div>
                            <div>{{ $selectedUnit->product_categories_allowed ?? 'N/A' }}</div>
                            <div class="text-gray-500">Approval Workflow</div>
                            <div>{{ $selectedUnit->approval_workflow ?? 'N/A' }}</div>
                            <div class="text-gray-500">Commission Structure</div>
                            <div>{{ $selectedUnit->commission_structure ?? 'N/A' }}</div>
                            <div class="text-gray-500">Unit Roles</div>
                            <div>{{ is_array($selectedUnit->unit_roles) ? implode(', ', $selectedUnit->unit_roles) : ($selectedUnit->unit_roles ?? 'N/A') }}</div>
                        </div>
                        <div class="border-t my-4"></div>
                        <div class="flex gap-2 items-center mt-2">
                            <livewire:organizations.apply-to-unit :unitId="$selectedUnit->id" />
                            <button class="btn btn-outline btn-sm" wire:click="exportUnitMembers({{ $selectedUnit->id }})">
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
