<div>
    <!-- Promote to Project Head Modal -->
    @if($showModal && $person)
    <div class="modal modal-open">
        <div class="modal-box max-w-lg">
            <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>

            <h3 class="font-bold text-lg mb-4">
                @if($isAlreadyProjectHead)
                    Manage Project Head Role
                @else
                    Promote to Project Head
                @endif
            </h3>

            <!-- Person Info -->
            <div class="bg-base-200 rounded-lg p-4 mb-4">
                <div class="flex items-center gap-4">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-12">
                            <span class="text-xl">{{ substr($person->given_name, 0, 1) }}{{ substr($person->family_name, 0, 1) }}</span>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold">{{ $person->given_name }} {{ $person->middle_name }} {{ $person->family_name }}</h4>
                        <p class="text-sm text-base-content/60">{{ $person->user?->email ?? 'No email' }}</p>
                        @if($isAlreadyProjectHead)
                            <span class="badge badge-success badge-sm mt-1">Currently Project Head</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($isAlreadyProjectHead)
                <!-- Already Project Head - Show Revoke Option -->
                <div class="alert alert-warning mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h3 class="font-bold">Revoke Project Head Role?</h3>
                        <div class="text-xs">This will remove their ability to manage project data and persons.</div>
                    </div>
                </div>

                <!-- Current Affiliations -->
                @if(!empty($currentAffiliations))
                <div class="mb-4">
                    <label class="label">
                        <span class="label-text text-sm font-medium">Current Affiliations</span>
                    </label>
                    <div class="space-y-2">
                        @foreach($currentAffiliations as $affiliation)
                        <div class="flex items-center justify-between bg-base-100 border rounded-lg p-3">
                            <div>
                                <p class="font-medium text-sm">{{ $affiliation['organization']['legal_name'] ?? $affiliation['organization']['display_name'] ?? 'Unknown' }}</p>
                                <p class="text-xs text-base-content/60">{{ $affiliation['role_title'] ?? 'No title' }}</p>
                            </div>
                            <span class="badge badge-sm {{ $affiliation['role_title'] === 'Project Head' ? 'badge-primary' : 'badge-ghost' }}">
                                {{ $affiliation['status'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="modal-action">
                    <button wire:click="closeModal" class="btn btn-ghost">Cancel</button>
                    <button wire:click="revokeProjectHead" class="btn btn-error">
                        <span wire:loading.remove wire:target="revokeProjectHead">Revoke Project Head Role</span>
                        <span wire:loading wire:target="revokeProjectHead" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>

            @else
                <!-- Not Project Head - Show Promotion Form -->
                <div class="alert alert-info mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold">Project Head Capabilities</h3>
                        <ul class="text-xs list-disc list-inside mt-1">
                            <li>Create and edit persons under the project</li>
                            <li>View and edit project details</li>
                            <li>Access project-specific modules and reports</li>
                        </ul>
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text text-sm font-medium">Select Project/Organization <span class="text-red-500">*</span></span>
                    </label>
                    @if(!empty($availableOrganizations))
                        <select wire:model="selectedOrganizationId" class="select select-bordered w-full">
                            <option value="">Select a project...</option>
                            @foreach($availableOrganizations as $org)
                                <option value="{{ $org['id'] }}">
                                    {{ $org['display_name'] ?? $org['legal_name'] ?? 'Unknown' }}
                                    @if(!empty($org['category']))
                                        ({{ ucfirst(strtolower(trim($org['category']))) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="input input-bordered w-full" value="No organizations available" readonly>
                    @endif
                    @error('selectedOrganizationId')
                        <span class="text-red-600 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Current Affiliations Info -->
                @if(!empty($currentAffiliations))
                <div class="mb-4">
                    <label class="label">
                        <span class="label-text text-sm font-medium">Current Affiliations</span>
                    </label>
                    <div class="space-y-2">
                        @foreach($currentAffiliations as $affiliation)
                        <div class="flex items-center justify-between bg-base-100 border rounded-lg p-3">
                            <div>
                                <p class="font-medium text-sm">{{ $affiliation['organization']['legal_name'] ?? $affiliation['organization']['display_name'] ?? 'Unknown' }}</p>
                                <p class="text-xs text-base-content/60">{{ $affiliation['role_title'] ?? 'No title' }}</p>
                            </div>
                            <span class="badge badge-ghost badge-sm">{{ $affiliation['status'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="modal-action">
                    <button wire:click="closeModal" class="btn btn-ghost">Cancel</button>
                    <button
                        wire:click="promoteToProjectHead"
                        class="btn btn-primary"
                        @if(empty($selectedOrganizationId)) disabled @endif
                    >
                        <span wire:loading.remove wire:target="promoteToProjectHead">Promote to Project Head</span>
                        <span wire:loading wire:target="promoteToProjectHead" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            @endif
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>
    @endif
</div>
