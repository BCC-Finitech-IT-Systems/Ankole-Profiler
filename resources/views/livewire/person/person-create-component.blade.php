<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Person Registry</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Add Person</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">

            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Personal Information</h2>
                <x-fill-sample-data-btn />
            </div>

            <form wire:submit.prevent="submit" class="p-6 space-y-6">

                {{-- Personal details --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Given Name <span class="text-red-500">*</span></span>
                        </label>
                        <input type="text" wire:model.defer="form.given_name" class="input input-bordered w-full"
                               placeholder="Enter given name">
                        @error('form.given_name')
                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Middle Name</span>
                        </label>
                        <input type="text" wire:model.defer="form.middle_name" class="input input-bordered w-full"
                               placeholder="Enter middle name">
                        @error('form.middle_name')
                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Family Name <span class="text-red-500">*</span></span>
                        </label>
                        <input type="text" wire:model.defer="form.family_name" class="input input-bordered w-full"
                               placeholder="Enter family name">
                        @error('form.family_name')
                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Date of Birth <span class="text-red-500">*</span></span>
                        </label>
                        <input type="date" wire:model.defer="form.date_of_birth" class="input input-bordered w-full">
                        @error('form.date_of_birth')
                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-sm font-medium">Gender <span class="text-red-500">*</span></span>
                        </label>
                        <select wire:model.defer="form.gender" class="select select-bordered w-full">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        @error('form.gender')
                            <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Contact --}}
                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">Phone Number <span class="text-red-500">*</span></span>
                            </label>
                            <input type="tel" wire:model.defer="form.phone" class="input input-bordered w-full"
                                   placeholder="+256 700 123 456">
                            @error('form.phone')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">Email Address <span class="text-red-500">*</span></span>
                            </label>
                            <input type="email" wire:model.defer="form.email" class="input input-bordered w-full"
                                   placeholder="jane.doe@email.com">
                            @error('form.email')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Address --}}
                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Address</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">Street Address <span class="text-red-500">*</span></span>
                            </label>
                            <textarea wire:model.defer="form.address" class="textarea textarea-bordered w-full"
                                      placeholder="Street address, building, apartment"></textarea>
                            @error('form.address')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">City <span class="text-red-500">*</span></span>
                                </label>
                                <input type="text" wire:model.defer="form.city" class="input input-bordered w-full"
                                       placeholder="City or town">
                                @error('form.city')
                                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">District <span class="text-red-500">*</span></span>
                                </label>
                                <input type="text" wire:model.defer="form.district" class="input input-bordered w-full"
                                       placeholder="District or region">
                                @error('form.district')
                                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">Country <span class="text-red-500">*</span></span>
                                </label>
                                <select wire:model.defer="form.country" class="select select-bordered w-full">
                                    <option value="Uganda">Uganda</option>
                                    <option value="Kenya">Kenya</option>
                                    <option value="Tanzania">Tanzania</option>
                                    <option value="Rwanda">Rwanda</option>
                                    <option value="United States">United States</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                </select>
                                @error('form.country')
                                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Organization & Role --}}
                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Organization & Role</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="form-control">
                            @if (auth()->user()->hasRole('Organization Admin') && !auth()->user()->hasRole('Super Admin'))
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">Project (Organization) <span class="text-red-500">*</span></span>
                                </label>
                                @if(!empty($availableOrganizations))
                                    <select wire:model.defer="form.organization_id" class="select select-bordered w-full">
                                        <option value="">Select Project</option>
                                        @foreach ($availableOrganizations as $org)
                                            <option value="{{ $org['id'] }}">
                                                {{ $org['display_name'] ?? $org['legal_name'] ?? 'No Project Provided' }}
                                                ({{ ucfirst(strtolower(trim($org['category'] ?? ''))) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($userDepartmentName)
                                        <p class="text-xs text-gray-400 mt-1">Showing organizations under {{ $userDepartmentName }} department</p>
                                    @endif
                                @else
                                    <input type="text" class="input input-bordered w-full"
                                           value="No organizations available for your account" readonly>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Your account is not linked to any organization yet. An administrator
                                        can add an affiliation from Person Management.
                                    </p>
                                @endif
                                @error('form.organization_id')
                                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            @elseif (auth()->user()->hasRole('Super Admin'))
                                <label class="label pb-1">
                                    <span class="label-text text-sm font-medium">Project <span class="text-red-500">*</span></span>
                                </label>
                                <select wire:model.defer="form.organization_id" class="select select-bordered w-full">
                                    <option value="">Select Project</option>
                                    @foreach ($availableOrganizations as $org)
                                        <option value="{{ $org['id'] }}">{{ $org['legal_name'] ?? 'No Project Provided' }}</option>
                                    @endforeach
                                </select>
                                @error('form.organization_id')
                                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        @if($canAssignProjectHead)
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">Assign as Project Head?</span>
                            </label>
                            <div class="flex items-center gap-3 mt-2">
                                <label class="label cursor-pointer gap-2">
                                    <input type="checkbox" wire:model.live="form.assign_as_project_head" class="checkbox" />
                                    <span class="label-text">Yes, assign as Project Head</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Project Heads can manage project data, create/edit persons, and access project-specific modules.</p>
                            @error('form.assign_as_project_head')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif

                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">Association Type</span>
                            </label>
                            <select wire:model.defer="form.role_type" class="select select-bordered w-full">
                                <option value="STAFF">Staff</option>
                                <option value="VOLUNTEER">Volunteer</option>
                                <option value="CONSULTANT">Consultant</option>
                                <option value="CONTRACTOR">Contractor</option>
                                <option value="INTERN">Intern</option>
                            </select>
                            @error('form.role_type')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-medium">
                                    {{ $form['assign_as_project_head'] ? 'Job Title' : 'Occupation' }}
                                    <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <input type="text" wire:model.defer="form.role_title" class="input input-bordered w-full"
                                   placeholder="{{ $form['assign_as_project_head'] ? 'e.g., Project Manager, Program Coordinator' : 'Enter role title' }}">
                            @error('form.role_title')
                                <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @if($canAssignProjectHead && $form['assign_as_project_head'])
                    <div class="alert alert-info mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold">Project Head Role</h3>
                            <div class="text-xs">
                                This person will be registered as a Project Head with the following capabilities:
                                <ul class="list-disc list-inside mt-1">
                                    <li>Create and edit persons under this project</li>
                                    <li>View and edit project details</li>
                                    <li>Access project-specific modules and reports</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Form actions --}}
                <div class="border-t border-gray-100 pt-6 flex justify-end gap-3">
                    <button type="button" wire:click="resetForm" class="btn btn-ghost btn-sm">
                        Reset Form
                    </button>
                    <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">
                        <span wire:loading.remove wire:target="submit">
                            {{ $form['assign_as_project_head'] ? 'Create Project Head' : 'Create Person' }}
                        </span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Processing…
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
