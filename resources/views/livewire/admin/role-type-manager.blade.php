<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Occupation Management</h2>
            <p class="text-gray-600">Manage Occupation & their permissions</p>
        </div>
        <button
            wire:click="openCreateModal"
            class="btn btn-primary"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Occupation
        </button>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('message'))
        <div class="alert alert-success mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search and Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <div class="flex gap-4">
                <div class="form-control flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search role types..."
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
                            <th>Project</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Permissions</th>
                            <th>Affiliations</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roleTypes as $roleType)
                            <tr>
                                <td>
                                    <div class="font-medium">
                                        {{ optional($roleType->organization)->legal_name ?? 'No Provided' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-mono font-medium">{{ $roleType->code }}</div>
                                </td>
                                <td>
                                    <div class="font-medium">{{ $roleType->name }}</div>
                                </td>
                                <td>
                                    <div class="max-w-xs truncate">
                                        {{ $roleType->description ?? 'No description' }}
                                    </div>
                                </td>
                                <td>
                                    @if($roleType->active)
                                        <div class="badge badge-success">Active</div>
                                    @else
                                        <div class="badge badge-error">Inactive</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="badge badge-info">
                                        {{ $roleType->permissions()->count() }} permissions
                                    </div>
                                </td>
                                <td>
                                    <div class="badge badge-warning">
                                        {{ $roleType->activeAffiliationsCount() }} active
                                    </div>
                                </td>
                                <td>{{ $roleType->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="flex gap-2">
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8">
                                    <div class="text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        <p>No Occupation found</p>
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

                @php $user = auth()->user(); @endphp
                @if($user && $user->hasRole('Super Admin'))
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Project *</span>
                    </label>
                    <select wire:model="organization_id" class="select select-bordered w-full @error('organization_id') select-error @enderror">
                        <option value="">Select Project</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                @else
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Project</span>
                    </label>
                    <input type="text" class="input input-bordered w-full bg-gray-100" value="{{ optional($organizations->first())->legal_name ?? 'No Provided' }}" readonly>
                </div>
                @endif
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Occupation Code *</span>
                    </label>
                    <input
                        wire:model="code"
                        type="text"
                        placeholder="e.g., MANAGER"
                        class="input input-bordered w-full @error('code') input-error @enderror"
                        style="text-transform: uppercase;"
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
                        placeholder="Optional description of this role type"
                        class="textarea textarea-bordered @error('description') textarea-error @enderror"
                        rows="3"
                    ></textarea>
                    @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-6">
                    <label class="label cursor-pointer justify-start">
                        <input
                            wire:model="active"
                            type="checkbox"
                            class="checkbox checkbox-primary mr-3"
                        >
                        <span class="label-text">Active</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn">Cancel</button>
                    <button wire:click="createRoleType" class="btn btn-primary">Create Occupation</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Role Type Modal -->
    @if($showEditModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">Edit Occupation</h3>

                @php $user = auth()->user(); @endphp
                @if($user && $user->hasRole('Super Admin'))
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Organization *</span>
                    </label>
                    <select wire:model="organization_id" class="select select-bordered w-full @error('organization_id') select-error @enderror">
                        <option value="">Select organization</option>
                        @foreach($organizations as $org)
                            <option value="{{  $org->legal_name ?? 'No Provided' }}</option>
                        @endforeach
                    </select>
                    @error('organization_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>
                @else
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Organization</span>
                    </label>
                    <input type="text" class="input input-bordered w-full bg-gray-100" value="{{ optional($organizations->first())->name ?? 'N/A' }}" readonly>
                </div>
                @endif
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Role Type Code *</span>
                    </label>
                    <input
                        wire:model="code"
                        type="text"
                        placeholder="e.g., MANAGER"
                        class="input input-bordered w-full @error('code') input-error @enderror"
                        style="text-transform: uppercase;"
                    >
                    @error('code') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Role Type Name *</span>
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
                        placeholder="Optional description of this role type"
                        class="textarea textarea-bordered @error('description') textarea-error @enderror"
                        rows="3"
                    ></textarea>
                    @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full mb-6">
                    <label class="label cursor-pointer justify-start">
                        <input
                            wire:model="active"
                            type="checkbox"
                            class="checkbox checkbox-primary mr-3"
                        >
                        <span class="label-text">Active</span>
                    </label>
                </div>

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn">Cancel</button>
                    <button wire:click="updateRoleType" class="btn btn-primary">Update Role Type</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Manage Permissions Modal -->
    @if($showPermissionsModal)
        <div class="modal modal-open">
            <div class="modal-box max-w-4xl">
                <h3 class="font-bold text-lg mb-4">Manage Role Type Permissions</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                    @foreach($permissions as $permission)
                        <div class="form-control">
                            <label class="label cursor-pointer justify-start">
                                <input
                                    type="checkbox"
                                    wire:model="selectedPermissions"
                                    value="{{ $permission->id }}"
                                    class="checkbox checkbox-primary mr-3"
                                >
                                <div>
                                    <span class="label-text font-medium">{{ $permission->name }}</span>
                                    @if($permission->description)
                                        <br><span class="label-text-alt text-gray-500">{{ $permission->description }}</span>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="modal-action">
                    <button wire:click="closeModals" class="btn">Cancel</button>
                    <button wire:click="updatePermissions" class="btn btn-primary">Update Permissions</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg text-error mb-4">Confirm Deletion</h3>
                <p class="py-4">Are you sure you want to delete this role type? This action cannot be undone.</p>
                <div class="modal-action">
                    <button wire:click="closeModals" class="btn">Cancel</button>
                    <button wire:click="deleteRoleType" class="btn btn-error">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
