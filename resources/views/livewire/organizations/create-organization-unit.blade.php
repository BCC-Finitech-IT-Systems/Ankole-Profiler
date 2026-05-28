{{-- Page wrapper gives the gray background proper breathing room --}}
<div class="min-h-full py-8 px-4 md:px-8">
<div class="w-full">

    {{-- Page header --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-widest font-medium mb-0.5">Projects Management</p>
            <h1 class="text-2xl font-bold text-gray-800">Create Organization Unit</h1>
            <p class="text-sm text-gray-400 mt-1">Register a new unit within the organizational hierarchy.</p>
        </div>
        <x-fill-sample-data-btn />
    </div>

    @if(session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Step indicator --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 px-6 py-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Progress</span>
            <span class="text-xs text-gray-400">Step {{ $currentStep }} of {{ $totalSteps }}</span>
        </div>
        {{-- Progress bar --}}
        <div class="w-full bg-gray-100 rounded-full h-1.5 mb-4">
            <div class="h-1.5 rounded-full transition-all duration-300"
                 style="background:#982B55; width:{{ round(($currentStep / $totalSteps) * 100) }}%"></div>
        </div>
        {{-- Step pills --}}
        <div class="grid grid-cols-4 gap-2">
            @php
                $stepLabels = [
                    1 => ['icon' => '🏛️', 'label' => 'Identity'],
                    2 => ['icon' => '👥', 'label' => 'Leadership'],
                    3 => ['icon' => '⚙️', 'label' => 'Operations'],
                    4 => ['icon' => '🧑‍🤝‍🧑', 'label' => 'Members'],
                ];
            @endphp
            @foreach($stepLabels as $step => $info)
                @php
                    $isActive = $currentStep === $step;
                    $isDone   = $currentStep > $step;
                @endphp
                <button type="button" wire:click="goToStep({{ $step }})"
                    class="flex flex-col items-center gap-1 py-2 px-1 rounded-lg text-center transition-colors
                           {{ $isActive ? 'bg-rose-50' : ($isDone ? 'hover:bg-gray-50' : 'hover:bg-gray-50 opacity-50') }}">
                    <span class="text-lg leading-none">
                        @if($isDone)
                            <svg class="w-5 h-5 mx-auto" fill="none" stroke="#982B55" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $info['icon'] }}
                        @endif
                    </span>
                    <span class="text-xs font-medium {{ $isActive ? 'text-rose-700' : ($isDone ? 'text-gray-500' : 'text-gray-400') }}">
                        {{ $info['label'] }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Form card --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <form wire:submit.prevent="submit">

            {{-- ── STEP 1: Identity & Structure ── --}}
            @if($currentStep === 1)
            <div class="p-6 space-y-5">
                <h3 class="text-base font-semibold text-gray-800">Identity & Structure</h3>

                {{-- Row 1: Org + Name + Code --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if(!empty($orgOptions))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Organization <span class="text-red-500">*</span></label>
                        <select wire:model.live="organization_id" class="select select-bordered w-full">
                            <option value="">Select…</option>
                            @foreach($orgOptions as $org)
                                <option value="{{ $org['id'] }}">{{ $org['legal_name'] }}</option>
                            @endforeach
                        </select>
                        @error('organization_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @else
                        <input type="hidden" wire:model="organization_id">
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="input input-bordered w-full" placeholder="e.g. Youth Ministry">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Code</label>
                        <input type="text" wire:model="code" class="input input-bordered w-full" placeholder="Auto-generated or custom">
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Row 2: Type + Dept + Parent Unit --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Type</label>
                        <input type="text" wire:model="unit_type" class="input input-bordered w-full" placeholder="e.g. Ministry, Committee">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Department
                            <span class="text-xs font-normal text-gray-400 ml-1">links to reporting</span>
                        </label>
                        @if(count($departmentOptions) > 0)
                            <select wire:model.live="department_id" class="select select-bordered w-full">
                                <option value="">— None —</option>
                                @foreach($departmentOptions as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        @else
                            <p class="text-xs text-gray-400 mt-2 italic">Select an organization first.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Unit</label>
                        <select wire:model="parent_unit_id" class="select select-bordered w-full">
                            <option value="">None (top-level)</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Row 3: Community + Ministry + Admin Office --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Community</label>
                        <input type="text" wire:model="community" class="input input-bordered w-full" placeholder="Community name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ministry / Committee</label>
                        <input type="text" wire:model="ministry_committee" class="input input-bordered w-full" placeholder="Ministry or committee">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Administrative Office</label>
                        <input type="text" wire:model="administrative_office" class="input input-bordered w-full" placeholder="Office name">
                    </div>
                </div>

                {{-- Description + Active --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" class="textarea textarea-bordered w-full" rows="3"
                              placeholder="Brief description of this unit's role and scope"></textarea>
                </div>

                <label class="flex items-center gap-2 cursor-pointer w-fit">
                    <input type="checkbox" wire:model="is_active" class="checkbox checkbox-sm">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
            </div>
            @endif

            {{-- ── STEP 2: Leadership & Purpose ── --}}
            @if($currentStep === 2)
            <div class="p-6 space-y-6">

                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                        Leadership & Governance
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Head / Leader</label>
                            <input type="text" wire:model="unit_head" class="input input-bordered w-full" placeholder="Name or person ID">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assistant Leader</label>
                            <input type="text" wire:model="assistant_leader" class="input input-bordered w-full" placeholder="Name or person ID">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Term</label>
                            <input type="text" wire:model="appointment_dates" class="input input-bordered w-full" placeholder="e.g. 2024–2026">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reporting Line</label>
                            <input type="text" wire:model="reporting_line" class="input input-bordered w-full" placeholder="Reports to which office">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Committee Members</label>
                            <input type="text" wire:model="leadership_committee" class="input input-bordered w-full" placeholder="Comma-separated names or IDs">
                        </div>
                    </div>
                </div>

                <div class="border-t pt-5 space-y-4">
                    <h3 class="text-base font-semibold text-gray-800">Purpose & Mission</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mission / Purpose Statement</label>
                        <textarea wire:model="mission" class="textarea textarea-bordered w-full" rows="2"
                                  placeholder="What is this unit's core purpose?"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Objectives</label>
                            <textarea wire:model="objectives" class="textarea textarea-bordered w-full" rows="2" placeholder="Key objectives"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Activities / Mandates</label>
                            <textarea wire:model="activities" class="textarea textarea-bordered w-full" rows="2" placeholder="Core activities or mandates"></textarea>
                        </div>
                    </div>
                    <div class="md:w-1/2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                        <input type="text" wire:model="target_audience" class="input input-bordered w-full" placeholder="e.g. youth, clergy, entrepreneurs">
                    </div>
                </div>
            </div>
            @endif

            {{-- ── STEP 3: Operations & Membership ── --}}
            @if($currentStep === 3)
            <div class="divide-y divide-gray-100">

                {{-- Contact --}}
                <div class="p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Official Email</label>
                            <input type="email" wire:model="official_email" class="input input-bordered w-full" placeholder="unit@diocese.org">
                            @error('official_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" wire:model="phone_contact" class="input input-bordered w-full" placeholder="+256 ...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Physical Location</label>
                            <input type="text" wire:model="physical_location" class="input input-bordered w-full" placeholder="Address or area">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                            <input type="text" wire:model="website" class="input input-bordered w-full" placeholder="https://...">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Social Media Links</label>
                            <input type="text" wire:model="social_links" class="input input-bordered w-full" placeholder="Facebook, X, Instagram…">
                        </div>
                    </div>
                </div>

                {{-- Operational --}}
                <div class="p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Operational Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Category</label>
                            <input type="text" wire:model="unit_category" class="input input-bordered w-full" placeholder="Category">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Operational Status</label>
                            <select wire:model="operational_status" class="select select-bordered w-full">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending Approval</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                            <input type="date" wire:model="date_established" class="input input-bordered w-full">
                        </div>
                        <div class="md:col-span-3 flex flex-wrap gap-6 pt-1">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" wire:model="faith_based" class="checkbox checkbox-sm"> Faith-based
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" wire:model="socio_economic" class="checkbox checkbox-sm"> Socio-economic
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" wire:model="support_services" class="checkbox checkbox-sm"> Support / Services
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Membership --}}
                <div class="p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Membership</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Membership Type</label>
                            <select wire:model="membership_type" class="select select-bordered w-full">
                                <option value="">Select…</option>
                                <option value="permanent">Permanent</option>
                                <option value="volunteer">Volunteer</option>
                                <option value="rotational">Rotational</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Eligibility Criteria</label>
                            <input type="text" wire:model="membership_eligibility" class="input input-bordered w-full" placeholder="Who can join?">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                            <input type="number" wire:model="membership_capacity" class="input input-bordered w-full" placeholder="Max members">
                        </div>
                        <div class="md:col-span-3">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer w-fit">
                                <input type="checkbox" wire:model="join_requests_enabled" class="checkbox checkbox-sm">
                                Allow public join requests
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Programs & Marketplace --}}
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Programs & Marketplace</h3>
                        <span class="badge badge-ghost badge-sm">optional</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Recurring Programs</label>
                            <input type="text" wire:model="recurring_programs" class="input input-bordered w-full" placeholder="e.g. Weekly prayer, Annual retreat">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Event Schedule</label>
                            <input type="text" wire:model="event_schedule" class="input input-bordered w-full" placeholder="e.g. Every Sunday 10am">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Promotion Permissions</label>
                            <input type="text" wire:model="promotion_permissions" class="input input-bordered w-full" placeholder="Who can promote events">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resource Access Requirements</label>
                            <input type="text" wire:model="resource_access_requirements" class="input input-bordered w-full" placeholder="Prerequisites for access">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Showcase Permissions</label>
                            <input type="text" wire:model="showcase_permissions" class="input input-bordered w-full" placeholder="Showcase permissions">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Categories Allowed</label>
                            <input type="text" wire:model="product_categories_allowed" class="input input-bordered w-full" placeholder="Allowed product categories">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Approval Workflow</label>
                            <input type="text" wire:model="approval_workflow" class="input input-bordered w-full" placeholder="How approvals work">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Commission / Fee Structure</label>
                            <input type="text" wire:model="commission_structure" class="input input-bordered w-full" placeholder="Commission or fee details">
                        </div>
                    </div>
                </div>

            </div>
            @endif

            {{-- ── STEP 4: Initial Members ── --}}
            @if($currentStep === 4)
            <div class="p-6 space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Initial Members</h3>
                    <p class="text-sm text-gray-400 mt-1">Optionally seed founding members now. You can also add members after creation from the unit details panel.</p>
                </div>

                @forelse($initialMembers as $i => $member)
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Member {{ $i + 1 }}</span>
                            <button type="button" wire:click="removeInitialMember({{ $i }})"
                                    class="text-xs text-red-400 hover:text-red-600 transition-colors">Remove</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Person</label>

                                @if(!empty($memberSelectedNames[$i]))
                                    {{-- Selected state --}}
                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg text-sm">
                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="flex-1 truncate text-green-800 font-medium">{{ $memberSelectedNames[$i] }}</span>
                                        <button type="button" wire:click="clearMemberPerson({{ $i }})"
                                            class="text-green-500 hover:text-red-500 transition-colors flex-shrink-0" title="Remove">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    {{-- Search input --}}
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live.debounce.300ms="memberSearchQueries.{{ $i }}"
                                            wire:input="searchMemberPerson({{ $i }}, $event.target.value)"
                                            class="input input-bordered input-sm w-full pr-8"
                                            placeholder="Search by name..." />
                                        <svg class="absolute right-2.5 top-2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z"/>
                                        </svg>

                                        @if(!empty($memberSearchResults[$i]))
                                            <div class="absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                                @foreach($memberSearchResults[$i] as $result)
                                                    <button type="button"
                                                        wire:click="selectMemberPerson({{ $i }}, {{ $result['id'] }}, '{{ addslashes($result['name']) }}')"
                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 border-b border-gray-100 last:border-0">
                                                        <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                                                            {{ strtoupper(substr($result['name'], 0, 1)) }}
                                                        </span>
                                                        <span>{{ $result['name'] }}</span>
                                                        <span class="ml-auto text-xs text-gray-400">#{{ $result['id'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @elseif(isset($memberSearchQueries[$i]) && strlen($memberSearchQueries[$i]) >= 2)
                                            <div class="absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-sm text-gray-400">
                                                No persons found.
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @error('initialMembers.' . $i . '.person_id')
                                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
                                <select wire:model="initialMembers.{{ $i }}.role" class="select select-bordered select-sm w-full">
                                    <option value="member">Member</option>
                                    <option value="leader">Leader</option>
                                    <option value="secretary">Secretary</option>
                                    <option value="treasurer">Treasurer</option>
                                    <option value="youth">Youth Coordinator</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-5 text-xs text-gray-600 pt-1">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model="initialMembers.{{ $i }}.can_view" class="checkbox checkbox-xs"> View
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model="initialMembers.{{ $i }}.can_edit" class="checkbox checkbox-xs"> Edit
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model="initialMembers.{{ $i }}.can_approve" class="checkbox checkbox-xs"> Approve
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 border border-dashed border-gray-200 rounded-lg">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm">No members added yet</p>
                        <p class="text-xs mt-0.5">Click "Add Member" below or skip — you can add them after creation.</p>
                    </div>
                @endforelse

                <button type="button" wire:click="addInitialMember"
                        class="btn btn-sm btn-outline gap-1.5" style="border-color:#982B55;color:#982B55;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Member
                </button>
            </div>
            @endif

            {{-- Navigation footer --}}
            <div class="flex justify-between items-center px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                <button type="button" wire:click="prevStep"
                        class="btn btn-ghost btn-sm gap-1 {{ $currentStep === 1 ? 'invisible' : '' }}">
                    &larr; Previous
                </button>
                @if($currentStep < $totalSteps)
                    <button type="button" wire:click="nextStep"
                            class="btn btn-sm border-0 text-white px-6" style="background:#982B55;">
                        Next &rarr;
                    </button>
                @else
                    <button type="submit" wire:loading.attr="disabled"
                            class="btn btn-sm border-0 text-white px-6" style="background:#982B55;">
                        <span wire:loading wire:target="submit">Creating…</span>
                        <span wire:loading.remove wire:target="submit">Create Unit</span>
                    </button>
                @endif
            </div>

        </form>
    </div>

</div>
</div>
