<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Permission Management</h1>
            </div>
            <button type="button" onclick="Livewire.dispatch('open-create-modal')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors flex-shrink-0"
                style="background:#982B55;">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Create Permission
            </button>
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

        {{-- Search --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search permissions..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Guard</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-left font-medium">Roles</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            <th class="px-4 py-3 text-left font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($permissions as $permission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $permission->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $permission->guard_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $permission->description ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">{{ $permission->roles()->count() }} roles</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $permission->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="openEditModal({{ $permission->id }})"
                                            class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="openDeleteModal({{ $permission->id }})"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-400">
                                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <p class="text-sm font-medium text-gray-500">No permissions found</p>
                                    <p class="text-xs text-gray-400 mt-1">Create your first permission to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $permissions->links() }}
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <h3 class="font-bold text-lg mb-4">Create New Permission</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Permission Name <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="e.g., manage-users"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Guard Name <span class="text-red-500">*</span></label>
                        <select wire:model="guardName" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                        @error('guardName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="Optional description"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="createPermission" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Create Permission</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <h3 class="font-bold text-lg mb-4">Edit Permission</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Permission Name <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="e.g., manage-users"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Guard Name <span class="text-red-500">*</span></label>
                        <select wire:model="guardName" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                        @error('guardName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="Optional description"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="updatePermission" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Update Permission</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box rounded-2xl">
                <h3 class="font-bold text-lg mb-2">Delete Permission</h3>
                <p class="text-sm text-gray-600 py-3">Are you sure you want to delete this permission? This action cannot be undone.</p>
                <div class="modal-action">
                    <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="deletePermission" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
