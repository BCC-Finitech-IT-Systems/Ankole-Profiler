<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Occupation Management</h2>
            <p class="text-gray-600">Manage Occupations & their permissions</p>
        </div>
        @if($canManage)
        <button
            wire:click="openCreateModal"
            class="btn btn-primary"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Occupation
        </button>
        @endif
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('message'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Search and Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="form-control flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search occupations..."
                        class="input input-bordered w-full"
                    >
                </div>
                <div class="form-control">
                    <select wire:model.live="activeFilter" class="select select-bordered">
                        <option value="all">All Status</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Types Table -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Permissions</th>
                            <th>Affiliations</th>
                            <th>Created</th>
                            @if($canManage)
                            <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roleTypes as $roleType)
                            <tr>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $roleType->department?->name ?? 'No Department' }}</span>
                                        <span class="text-xs text-gray-500">{{ $roleType->department?->organization?->legal_name ?? '' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-mono font-medium text-sm bg-base-200 px-2 py-1 rounded inline-block">
                                        {{ $roleType->code }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium">{{ $roleType->name }}</div>
                                </td>
                                <td>
                                    <div class="max-w-xs truncate text-sm text-gray-600">
                                        {{ $roleType->description ?? 'No description' }}
                                    </div>
                                </td>
                                <td>
                                    @if($roleType->active)
                                        <div class="badge badge-success gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Active
                                        </div>
                                    @else
                                        <div class="badge badge-error gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            Inactive
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="badge badge-info badge-outline">
                                        {{ $roleType->permissions->count() }} permissions
                                    </div>
                                </td>
                                <td>
                                    @if(method_exists($roleType, 'activeAffiliationsCount'))
                                    <div class="badge badge-warning badge-outline">
                                        {{ $roleType->activeAffiliationsCount() }} active
                                    </div>
                                    @else
                                    <div class="badge badge-ghost">N/A</div>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">{{ $roleType->created_at->format('M d, Y') }}</td>
                                @if($canManage)
                                <td>
                                    <div class="flex gap-1">
                                        <button
                                            wire:click="openPermissionsModal({{ $roleType->id }})"
                                            class="btn btn-sm btn-ghost text-info"
                                            title="Manage Permissions"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="toggleStatus({{ $roleType->id }})"
                                            class="btn btn-sm btn-ghost {{ $roleType->active ? 'text-warning' : 'text-success' }}"
                                            title="{{ $roleType->active ? 'Deactivate' : 'Activate' }}"
                                        >
                                            @if($roleType->active)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @endif
                                        </button>
                                        <button
                                            wire:click="openEditModal({{ $roleType->id }})"
                                            class="btn btn-sm btn-ghost"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="openDeleteModal({{ $roleType->id }})"
                                            class="btn btn-sm btn-ghost text-error"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 9 : 8 }}" class="text-center py-8">
                                    <div class="text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        <p class="font-medium">No Occupations found</p>
                                        <p class="text-sm">Create your first Occupation to get started</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-4">
                {{ $roleTypes->links() }}
            </div>
        </div>
    </div>

    <!-- Create Role Type Modal -->
    @if($showCreateModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">Create New Occupation</h3>

                <!-- Department Selection -->
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Department *</span>
                    </label>
                    @if($departments->isEmpty())
                        {{-- No departments available --}}
                        <div class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>No departments found. Please ensure departments exist for your organization.</span>
                        </div>
                    @elseif($departments->count() === 1)
                        {{-- Single department - show read-only --}}
                        <input 
                            type="text" 
                            class="input input-bordered w-full bg-base-200" 
                            value="{{ $departments->first()->name }} ({{ $departments->first()->organization?->legal_name ?? 'N/A' }})" 
                            readonly
                        >
                        <label class="label">
                            <span class="label-text-alt text-gray-500">Auto-selected based on your affiliation</span>
                        </label>
                    @else
                        {{-- Multiple departments - show dropdown --}}
                        <select 
                            wire:model="department_id" 
                            class="select select-bordered w-full @error('department_id') select-error @enderror"
                        >
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">
                                    {{ $dept->name }}
                                    @if($dept->organization)
                                        ({{ $dept->organization->legal_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('department_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Occupation Code *</span>
                    </label>
                    <input
                        wire:model="code"
                        type="text"
                        placeholder="e.g., MANAGER"
                        class="input input-bordered w-full uppercase @error('code') input-error @enderror"
                    >
                    <label class="label">
                        <span class="label-text-alt text-gray-500">Will be automatically converted to uppercase</span>
                    </label>
                    @error('code') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Occupation Name *</span>
                    </label>
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="e.g., Department Manager"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                    >
                    @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea
                        wire:model="description"
                        placeholder="Optional description of this occupation"
                        class="textarea textarea-bordered @error('description') textarea-error @enderror"
                        rows="3"
                    ></textarea>
                    @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-6">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input
                            wire:model="active"
                            type="checkbox"
                            class="checkbox checkbox-primary"
                        >
                        <span class="label-text">Active</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button 
                        wire:click="createRoleType" 
                        class="btn btn-primary"
                        @if($departments->isEmpty()) disabled @endif
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Occupation
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    <!-- Edit Role Type Modal -->
    @if($showEditModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">Edit Occupation</h3>

                <!-- Department Selection -->
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Department *</span>
                    </label>
                    @if($departments->count() === 1)
                        {{-- Single department - show read-only --}}
                        <input 
                            type="text" 
                            class="input input-bordered w-full bg-base-200" 
                            value="{{ $departments->first()->name }} ({{ $departments->first()->organization?->legal_name ?? 'N/A' }})" 
                            readonly
                        >
                    @else
                        {{-- Multiple departments - show dropdown --}}
                        <select 
                            wire:model="department_id" 
                            class="select select-bordered w-full @error('department_id') select-error @enderror"
                        >
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">
                                    {{ $dept->name }}
                                    @if($dept->organization)
                                        ({{ $dept->organization->legal_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('department_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Occupation Code *</span>
                    </label>
                    <input
                        wire:model="code"
                        type="text"
                        placeholder="e.g., MANAGER"
                        class="input input-bordered w-full uppercase @error('code') input-error @enderror"
                    >
                    @error('code') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Occupation Name *</span>
                    </label>
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="e.g., Department Manager"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                    >
                    @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea
                        wire:model="description"
                        placeholder="Optional description of this occupation"
                        class="textarea textarea-bordered @error('description') textarea-error @enderror"
                        rows="3"
                    ></textarea>
                    @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-6">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input
                            wire:model="active"
                            type="checkbox"
                            class="checkbox checkbox-primary"
                        >
                        <span class="label-text">Active</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button wire:click="updateRoleType" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Occupation
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    <!-- Manage Permissions Modal -->
    @if($showPermissionsModal)
        <div class="modal modal-open">
            <div class="modal-box max-w-4xl">
                <h3 class="font-bold text-lg mb-4">Manage Occupation Permissions</h3>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Select the permissions that should be assigned to this occupation.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto p-2">
                    @foreach($permissions as $permission)
                        <div class="form-control bg-base-200 rounded-lg p-3">
                            <label class="label cursor-pointer justify-start gap-3">
                                <input
                                    type="checkbox"
                                    wire:model="selectedPermissions"
                                    value="{{ $permission->id }}"
                                    class="checkbox checkbox-primary checkbox-sm"
                                >
                                <div class="flex flex-col">
                                    <span class="label-text font-medium text-sm">{{ $permission->name }}</span>
                                    @if($permission->description)
                                        <span class="text-xs text-gray-500">{{ $permission->description }}</span>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                @if($permissions->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p>No permissions available.</p>
                    </div>
                @endif

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button wire:click="updatePermissions" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Update Permissions
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg text-error mb-4">
                    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Confirm Deletion
                </h3>
                <p class="py-4">Are you sure you want to delete this occupation? This action cannot be undone.</p>
                <div class="alert alert-warning mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>This will also remove all permission associations.</span>
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button wire:click="deleteRoleType" class="btn btn-error">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif
</div>