<div>
    <div class="max-w-7xl px-4 py-4">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Create New Person</h2>
            <form wire:submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Given Name <span
                                    class="text-red-500">*</span></span>
                        </label>
                        <input type="text" wire:model.defer="form.given_name" class="input input-bordered w-full"
                            placeholder="Enter given name">
                        @error('form.given_name')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Middle Name</span>
                        </label>
                        <input type="text" wire:model.defer="form.middle_name" class="input input-bordered w-full"
                            placeholder="Enter middle name">
                        @error('form.middle_name')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Family Name <span
                                    class="text-red-500">*</span></span>
                        </label>
                        <input type="text" wire:model.defer="form.family_name" class="input input-bordered w-full"
                            placeholder="Enter family name">
                        @error('form.family_name')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Date of Birth</span>
                        </label>
                        <input type="date" wire:model.defer="form.date_of_birth" class="input input-bordered w-full">
                        @error('form.date_of_birth')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Gender</span>
                        </label>
                        <select wire:model.defer="form.gender" class="select select-bordered w-full">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        @error('form.gender')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Contact Information -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Phone Number</span>
                        </label>
                        <input type="tel" wire:model.defer="form.phone" class="input input-bordered w-full"
                            placeholder="+256 700 123 456">
                        @error('form.phone')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Email Address</span>
                        </label>
                        <input type="email" wire:model.defer="form.email" class="input input-bordered w-full"
                            placeholder="jane.doe@email.com">
                        @error('form.email')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Address Information -->
                    <div class="form-control md:col-span-1">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Street Address</span>
                        </label>
                        <textarea wire:model.defer="form.address" class="textarea textarea-bordered w-full"
                            placeholder="Street address, building, apartment"></textarea>
                        @error('form.address')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">City</span>
                        </label>
                        <input type="text" wire:model.defer="form.city" class="input input-bordered w-full"
                            placeholder="City or town">
                        @error('form.city')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">District</span>
                        </label>
                        <input type="text" wire:model.defer="form.district" class="input input-bordered w-full"
                            placeholder="District or region">
                        @error('form.district')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Country <span
                                    class="text-red-500">*</span></span>
                        </label>
                        <select wire:model.defer="form.country" class="select select-bordered w-full">
                            <option value="UGA">Uganda</option>
                            <option value="KEN">Kenya</option>
                            <option value="TZA">Tanzania</option>
                            <option value="RWA">Rwanda</option>
                            <option value="USA">United States</option>
                            <option value="GBR">United Kingdom</option>
                        </select>
                        @error('form.country')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Organization Information -->
                    <div class="form-control">
                        @if (auth()->user()->hasRole('Organization Admin'))
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">Project <span
                                            class="text-red-500">*</span></span>
                                </label>
                                <input type="text" class="input input-bordered w-full"
                                    value="{{ auth()->user()->personAffiliation->organization->display_name ?? auth()->user()->personAffiliation->organization->legal_name }}"
                                    readonly>
                                <input type="hidden" wire:model.defer="currentAffiliation.organization_id"
                                    value="{{ auth()->user()->personAffiliation->organization_id }}">
                            </div>
                        @elseif (auth()->user()->hasRole('Super Admin'))
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">Project <span
                                            class="text-red-500">*</span></span>
                                </label>
                                <select wire:model.defer="currentAffiliation.organization_id"
                                    class="select select-bordered w-full">
                                    <option value="">Select Project</option>
                                    @foreach ($availableOrganizations as $org)
                                        <option value="{{ $org['id'] }}">
                                            {{ $org['legal_name'] ?? 'No Project Provided' }}</option>
                                    @endforeach
                                </select>
                                @error('currentAffiliation.organization_id')
                                    <span class="text-red-600 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Role Type</span>
                        </label>
                        <input type="text" wire:model.defer="form.role_type" class="input input-bordered w-full"
                            placeholder="Enter role type">
                        @error('form.role_type')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Role Title</span>
                        </label>
                        <input type="text" wire:model.defer="form.role_title" class="input input-bordered w-full"
                            placeholder="Enter role title">
                        @error('form.role_title')
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading.remove wire:target="submit">Create Person</span>
                        <span wire:loading wire:target="submit" class="flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
