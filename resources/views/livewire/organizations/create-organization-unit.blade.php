<div class="max-w-6xl mt-2 bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Register Project Unit</h2>
    @if(session()->has('success'))
        <div class="bg-green-100 text-green-800 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stepper Navigation --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex gap-6">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <button type="button" wire:click="goToStep({{ $i }})"
                        class="w-8 h-8 rounded-full flex items-center justify-center {{ $currentStep === $i ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600' }} border border-primary/30 text-sm font-bold">
                        {{ $i }}
                    </button>
                @endfor
            </div>
            <span class="text-sm text-gray-500">Step {{ $currentStep }} of {{ $totalSteps }}</span>
        </div>
    </div>

    <form wire:submit.prevent="submit">
        {{-- Step 1: Basic Info --}}

        @if($currentStep === 1)
            <div>
                <h3 class="font-semibold text-lg mb-2">1. Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if(!empty($orgOptions) && count($orgOptions) > 0)
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Parent Organization <span class="text-red-500">*</span></label>
                            <select wire:model="organization_id" class="input input-bordered w-full" required>
                                <option value="">Select Organization</option>
                                @foreach($orgOptions as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error('organization_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <input type="hidden" wire:model="organization_id">
                    @endif
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Unit Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="input input-bordered w-full" required placeholder="Unit Name">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Unit Code / Reference Number</label>
                        <input type="text" wire:model="code" class="input input-bordered w-full" placeholder="Auto-generated or custom">
                        @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Unit Type</label>
                        <input type="text" wire:model="unit_type" class="input input-bordered w-full" placeholder="e.g. Ministry, Committee, Department">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <input type="text" wire:model="department" class="input input-bordered w-full" placeholder="Department">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Community</label>
                        <input type="text" wire:model="community" class="input input-bordered w-full" placeholder="Community">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Ministry/Committee</label>
                        <input type="text" wire:model="ministry_committee" class="input input-bordered w-full" placeholder="Ministry/Committee">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Administrative Office</label>
                        <input type="text" wire:model="administrative_office" class="input input-bordered w-full" placeholder="Administrative Office">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Parent Unit</label>
                        <select wire:model="parent_unit_id" class="input input-bordered w-full">
                            <option value="">None</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="description" class="input input-bordered w-full" rows="2" placeholder="Description"></textarea>
                    </div>
                    <div class="flex items-center gap-2 mb-3 md:col-span-3">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="checkbox">
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 2: Leadership & Governance --}}
    @if($currentStep === 2)
            <div>
                <h3 class="font-semibold text-lg mb-2">2. Leadership & Governance</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Unit Head / Leader</label>
                        <input type="text" wire:model="unit_head" class="input input-bordered w-full" placeholder="Search or select person">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Assistant Leader</label>
                        <input type="text" wire:model="assistant_leader" class="input input-bordered w-full" placeholder="Search or select person">
                    </div>
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Leadership Committee Members</label>
                        <input type="text" wire:model="leadership_committee" class="input input-bordered w-full" placeholder="Add multiple persons (comma separated)">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Appointment Dates / Terms</label>
                        <input type="text" wire:model="appointment_dates" class="input input-bordered w-full" placeholder="e.g. 2024-2026">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Reporting Line</label>
                        <input type="text" wire:model="reporting_line" class="input input-bordered w-full" placeholder="To which office/leader">
                    </div>
                </div>
            </div>

        {{-- Step 3: Purpose & Mission --}}
        @endif
        @if($currentStep === 3)
            <div>
                <h3 class="font-semibold text-lg mb-2">3. Purpose & Mission</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Mission / Purpose Statement</label>
                        <textarea wire:model="mission" class="input input-bordered w-full" rows="2" placeholder="Mission or purpose statement"></textarea>
                    </div>
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Objectives</label>
                        <textarea wire:model="objectives" class="input input-bordered w-full" rows="2" placeholder="Objectives"></textarea>
                    </div>
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Activities / Mandates</label>
                        <textarea wire:model="activities" class="input input-bordered w-full" rows="2" placeholder="Activities or mandates"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Target Audience</label>
                        <input type="text" wire:model="target_audience" class="input input-bordered w-full" placeholder="e.g. youth, clergy, entrepreneurs">
                    </div>
                </div>
            </div>

        {{-- Step 4: Contact Information --}}
        @endif
        @if($currentStep === 4)
            <div>
                <h3 class="font-semibold text-lg mb-2">4. Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Official Email</label>
                        <input type="email" wire:model="official_email" class="input input-bordered w-full" placeholder="Email">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Phone Contact</label>
                        <input type="text" wire:model="phone_contact" class="input input-bordered w-full" placeholder="Phone">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Physical Location</label>
                        <input type="text" wire:model="physical_location" class="input input-bordered w-full" placeholder="Physical Location">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Website</label>
                        <input type="text" wire:model="website" class="input input-bordered w-full" placeholder="Website">
                    </div>
                    <div class="mb-3 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Social Media Links</label>
                        <input type="text" wire:model="social_links" class="input input-bordered w-full" placeholder="Social Media Links">
                    </div>
                </div>
            </div>

        {{-- Step 5: Operational Details --}}
        @endif
        @if($currentStep === 5)
            <div>
                <h3 class="font-semibold text-lg mb-2">5. Operational Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Unit Category</label>
                        <input type="text" wire:model="unit_category" class="input input-bordered w-full" placeholder="Unit Category">
                    </div>
                    <div class="mb-3 flex items-center gap-2">
                        <input type="checkbox" wire:model="faith_based" class="checkbox">
                        <label class="text-sm font-medium text-gray-700">Faith-based</label>
                    </div>
                    <div class="mb-3 flex items-center gap-2">
                        <input type="checkbox" wire:model="socio_economic" class="checkbox">
                        <label class="text-sm font-medium text-gray-700">Socio-economic</label>
                    </div>
                    <div class="mb-3 flex items-center gap-2">
                        <input type="checkbox" wire:model="support_services" class="checkbox">
                        <label class="text-sm font-medium text-gray-700">Support/Services</label>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Operational Status</label>
                        <select wire:model="operational_status" class="input input-bordered w-full">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending Approval</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Date Established</label>
                        <input type="date" wire:model="date_established" class="input input-bordered w-full">
                    </div>
                </div>
            </div>

        {{-- Step 6: Membership Metadata --}}
        @endif
        @if($currentStep === 6)
            <div>
                <h3 class="font-semibold text-lg mb-2">6. Membership Metadata</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Membership Type</label>
                        <select wire:model="membership_type" class="input input-bordered w-full">
                            <option value="">Select Type</option>
                            <option value="permanent">Permanent</option>
                            <option value="volunteer">Volunteer</option>
                            <option value="rotational">Rotational</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Membership Eligibility Criteria</label>
                        <input type="text" wire:model="membership_eligibility" class="input input-bordered w-full" placeholder="Eligibility Criteria">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Membership Capacity</label>
                        <input type="number" wire:model="membership_capacity" class="input input-bordered w-full" placeholder="Capacity">
                    </div>
                    <div class="mb-3 flex items-center gap-2 md:col-span-3">
                        <input type="checkbox" wire:model="join_requests_enabled" id="join_requests_enabled" class="checkbox">
                        <label for="join_requests_enabled" class="text-sm font-medium text-gray-700">Join Requests Enabled?</label>
                    </div>
                </div>
            </div>

        {{-- Step 7: Events & Programs Metadata --}}
        @endif
        @if($currentStep === 7)
            <div>
                <h3 class="font-semibold text-lg mb-2">7. Events & Programs Metadata</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Recurring Programs</label>
                        <input type="text" wire:model="recurring_programs" class="input input-bordered w-full" placeholder="Recurring Programs">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Event Schedule</label>
                        <input type="text" wire:model="event_schedule" class="input input-bordered w-full" placeholder="Event Schedule">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Promotion Permissions</label>
                        <input type="text" wire:model="promotion_permissions" class="input input-bordered w-full" placeholder="Promotion Permissions">
                    </div>
                    <div class="mb-3 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Resource Access Requirements</label>
                        <input type="text" wire:model="resource_access_requirements" class="input input-bordered w-full" placeholder="Resource Access Requirements">
                    </div>
                </div>
            </div>

        {{-- Step 8: Showcase & Marketplace Support --}}
        @endif
        @if($currentStep === 8)
            <div>
                <h3 class="font-semibold text-lg mb-2">8. Showcase & Marketplace Support</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Showcase Permissions</label>
                        <input type="text" wire:model="showcase_permissions" class="input input-bordered w-full" placeholder="Showcase Permissions">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Product Categories Allowed</label>
                        <input type="text" wire:model="product_categories_allowed" class="input input-bordered w-full" placeholder="Product Categories Allowed">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Approval Workflow</label>
                        <input type="text" wire:model="approval_workflow" class="input input-bordered w-full" placeholder="Approval Workflow">
                    </div>
                    <div class="mb-3 md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Commission/Fee Structure</label>
                        <input type="text" wire:model="commission_structure" class="input input-bordered w-full" placeholder="Commission/Fee Structure">
                    </div>
                </div>
            </div>

        {{-- Step 9: Roles & Permissions for Unit Users --}}
        @endif
        @if($currentStep === 9)
            <div>
                <h3 class="font-semibold text-lg mb-2">9. Roles & Permissions for Unit Users</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Roles</label>
                        <select wire:model="unit_roles" class="input input-bordered w-full" multiple>
                            <option value="admin">Unit Admin</option>
                            <option value="moderator">Moderator</option>
                            <option value="publisher">Content Publisher</option>
                            <option value="member">Member</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
            </div>

        @endif
        <div class="flex justify-between mt-8">
            <button type="button" wire:click="prevStep" class="btn btn-secondary" @if($currentStep === 1) disabled @endif>Previous</button>
            @if($currentStep < $totalSteps)
                <button type="button" wire:click="nextStep" class="btn btn-primary">Next</button>
            @else
                <button type="submit" class="btn btn-success">Submit</button>
            @endif
        </div>
    </form>
</div>
