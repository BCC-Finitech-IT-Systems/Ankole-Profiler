<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Role Management</h1>
            </div>
            <button wire:click="openCreateModal"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white flex-shrink-0"
                style="background:#982B55;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Role
            </button>
        </div>
    </x-slot>

    <div class="p-4 space-y-4">
        @if (session()->has('message'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Search --}}
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search roles..."
                class="flex-1 text-sm outline-none bg-transparent text-gray-700 placeholder-gray-400">
        </div>

        {{-- Roles Table --}}
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Guard</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Permissions</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Users</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($roles as $role)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $role->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $role->guard_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                                    {{ $role->description ?? 'No description' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white"
                                          style="background:#982B55;">
                                        {{ $role->permissions()->count() }} permissions
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        {{ $role->users()->count() }} users
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ $role->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="openPermissionsModal({{ $role->id }})" title="Manage Permissions"
                                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="openEditModal({{ $role->id }})" title="Edit"
                                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="openDeleteModal({{ $role->id }})" title="Delete"
                                            class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-16">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No roles found</p>
                                    <p class="text-xs text-gray-500">Create your first role to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($roles->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Create Role Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50" wire:click="closeModals"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md z-10 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">Create New Role</h3>
                        <x-fill-sample-data-btn />
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g., Content Manager"
                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2"
                                style="--tw-ring-color:#982B55;">
                            @error('name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Guard <span class="text-red-500">*</span></label>
                            <select wire:model="guardName" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2" style="--tw-ring-color:#982B55;">
                                <option value="web">web</option>
                                <option value="api">api</option>
                            </select>
                            @error('guardName') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                            <textarea wire:model="description" rows="3" placeholder="Optional description"
                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2"
                                style="--tw-ring-color:#982B55;"></textarea>
                            @error('description') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="createRole" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors" style="background:#982B55;">Create Role</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Role Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50" wire:click="closeModals"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md z-10 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Edit Role</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g., Content Manager"
                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2"
                                style="--tw-ring-color:#982B55;">
                            @error('name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Guard</label>
                            <select wire:model="guardName" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2" style="--tw-ring-color:#982B55;">
                                <option value="web">web</option>
                                <option value="api">api</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                            <textarea wire:model="description" rows="3" placeholder="Optional description"
                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2"
                                style="--tw-ring-color:#982B55;"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="updateRole" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors" style="background:#982B55;">Update Role</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Manage Permissions Modal --}}
    @if($showPermissionsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50" wire:click="closeModals"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl z-10">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Manage Role Permissions</h3>
                        <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 flex-shrink-0"
                                        style="accent-color:#982B55;">
                                    <div class="min-w-0">
                                        <div class="text-xs font-medium text-gray-800">{{ $permission->name }}</div>
                                        @if($permission->description)
                                            <div class="text-xs text-gray-400 truncate">{{ $permission->description }}</div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-end gap-3">
                        <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="updatePermissions" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors" style="background:#982B55;">Update Permissions</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50" wire:click="closeModals"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm z-10 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Delete Role</h3>
                            <p class="text-sm text-gray-500">Are you sure you want to delete this role? This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                        <button wire:click="deleteRole" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
