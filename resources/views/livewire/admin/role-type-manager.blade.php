<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Occupation Management</h1>
            </div>
            @if($canManage)
                <button type="button" onclick="Livewire.dispatch('open-create-modal')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors flex-shrink-0"
                    style="background:#982B55;">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                    Create Occupation
                </button>
            @endif
        </div>
    </x-slot>

    <div class="p-6 space-y-4">

        @if (session()->has('message'))
            <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Search & Filters --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search occupations..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                </div>
                <select wire:model.live="activeFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Department</th>
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Permissions</th>
                            <th class="px-4 py-3 text-left font-medium">Affiliations</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            @if($canManage)
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($roleTypes as $roleType)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800">{{ $roleType->department?->name ?? 'No Department' }}</p>
                                    <p class="text-xs text-gray-500">{{ $roleType->department?->organization?->legal_name ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $roleType->code }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $roleType->name }}</td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate text-xs">{{ $roleType->description ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($roleType->active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">{{ $roleType->permissions->count() }} perms</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if(method_exists($roleType, 'activeAffiliationsCount'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">{{ $roleType->activeAffiliationsCount() }} active</span>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $roleType->created_at->format('M d, Y') }}</td>
                                @if($canManage)
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <button wire:click="openPermissionsModal({{ $roleType->id }})"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Manage Permissions">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            </button>
                                            <button wire:click="toggleStatus({{ $roleType->id }})"
                                                class="p-1.5 rounded-lg transition-colors {{ $roleType->active ? 'text-gray-400 hover:text-amber-600 hover:bg-amber-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}"
                                                title="{{ $roleType->active ? 'Deactivate' : 'Activate' }}">
                                                @if($roleType->active)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </button>
                                            <button wire:click="openEditModal({{ $roleType->id }})"
                                                class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="openDeleteModal({{ $roleType->id }})"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 9 : 8 }}" class="text-center py-12 text-gray-400">
                                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <p class="text-sm font-medium text-gray-500">No Occupations found</p>
                                    <p class="text-xs text-gray-400 mt-1">Create your first Occupation to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $roleTypes->links() }}
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-lg">Create New Occupation</h3>
                    <x-fill-sample-data-btn />
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Department <span class="text-red-500">*</span></label>
                        @if($departments->isEmpty())
                            <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                No departments found. Please ensure departments exist for your organization.
                            </div>
                        @elseif($departments->count() === 1)
                            <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
                                value="{{ $departments->first()->name }} ({{ $departments->first()->organization?->legal_name ?? 'N/A' }})" readonly>
                            <p class="text-xs text-gray-500 mt-1">Auto-selected based on your affiliation</p>
                        @else
                            <select wire:model="department_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}{{ $dept->organization ? ' ('.$dept->organization->legal_name.')' : '' }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Occupation Code <span class="text-red-500">*</span></label>
                        <input wire:model="code" type="text" placeholder="e.g., MANAGER" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-rose-300">
                        <p class="text-xs text-gray-500 mt-1">Will be automatically converted to uppercase</p>
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Occupation Name <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="e.g., Department Manager" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="Optional description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input wire:model="active" type="checkbox" class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="createRoleType" class="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-50" style="background:#982B55;" @if($departments->isEmpty()) disabled @endif>Create Occupation</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <h3 class="font-bold text-lg mb-4">Edit Occupation</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Department <span class="text-red-500">*</span></label>
                        @if($departments->count() === 1)
                            <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
                                value="{{ $departments->first()->name }} ({{ $departments->first()->organization?->legal_name ?? 'N/A' }})" readonly>
                        @else
                            <select wire:model="department_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}{{ $dept->organization ? ' ('.$dept->organization->legal_name.')' : '' }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Occupation Code <span class="text-red-500">*</span></label>
                        <input wire:model="code" type="text" placeholder="e.g., MANAGER" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Occupation Name <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="e.g., Department Manager" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="Optional description" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input wire:model="active" type="checkbox" class="w-4 h-4 rounded border-gray-300" style="accent-color:#982B55;">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="updateRoleType" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Update Occupation</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    {{-- Permissions Modal --}}
    @if($showPermissionsModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl max-w-4xl">
                <h3 class="font-bold text-lg mb-2">Manage Occupation Permissions</h3>
                <p class="text-sm text-gray-500 mb-4">Select the permissions that should be assigned to this occupation.</p>
                @if($permissions->isEmpty())
                    <p class="text-center py-8 text-gray-400 text-sm">No permissions available.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-96 overflow-y-auto">
                        @foreach($permissions as $permission)
                            <label class="flex items-start gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}"
                                    class="w-4 h-4 rounded border-gray-300 mt-0.5 flex-shrink-0" style="accent-color:#982B55;">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $permission->name }}</p>
                                    @if($permission->description)
                                        <p class="text-xs text-gray-500">{{ $permission->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="updatePermissions" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Update Permissions</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <h3 class="font-bold text-lg mb-2">Delete Occupation</h3>
                <p class="text-sm text-gray-600 py-3">Are you sure you want to delete this occupation? This action cannot be undone.</p>
                <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 mb-4">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    This will also remove all permission associations.
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="deleteRoleType" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeModals"></div>
        </div>
    @endif
</div>
