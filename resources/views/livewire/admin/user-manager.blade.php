<div>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Admin</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">User Management</h1>
            </div>
            @if(!empty($selectedUsers))
                <button wire:click="bulkDelete"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition-colors flex-shrink-0">
                    Delete Selected ({{ count($selectedUsers) }})
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

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                <button wire:click="$set('activeTab', 'users')"
                    class="py-2.5 px-1 border-b-2 text-sm font-medium transition-colors flex items-center gap-2
                        {{ $activeTab === 'users' ? 'border-rose-600 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Users
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-semibold
                        {{ $activeTab === 'users' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $users->total() }}
                    </span>
                </button>
                <button wire:click="$set('activeTab', 'emails-phones')"
                    class="py-2.5 px-1 border-b-2 text-sm font-medium transition-colors
                        {{ $activeTab === 'emails-phones' ? 'border-rose-600 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Emails & Phones
                </button>
            </nav>
        </div>

        @if ($activeTab === 'users')

            {{-- Search & Filters --}}
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search users by name or email..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                </div>
                <select wire:model.live="roleFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                    <option value="all">All Roles</option>
                    @foreach ($allRoles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="organizationFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                    <option value="all">All Organizations</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->legal_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Users Table --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8">
                                    <input type="checkbox" wire:model="selectAll" class="checkbox checkbox-sm" title="Select All">
                                </th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Organization</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roles</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($users as $user)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}"
                                            class="checkbox checkbox-sm" title="Select User">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#982B55;">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-700">{{ $user->email }}</div>
                                        @if ($user->email_verified_at)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[11px] font-medium bg-green-100 text-green-700">Verified</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-700">Unverified</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->personAffiliation && $user->personAffiliation->Organization)
                                            <div class="font-medium text-gray-900">{{ $user->personAffiliation->Organization->legal_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->personAffiliation->Organization->code }}</div>
                                        @else
                                            <span class="text-gray-400 italic text-xs">No organization</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($user->roles as $role)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-gray-400 text-xs">No roles</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->email_verified_at)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="openRolesModal({{ $user->id }})"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Manage Roles">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </button>
                                            <button wire:click="openOrganizationModal({{ $user->id }})"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Manage Organization">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            </button>
                                            <button wire:click="toggleUserStatus({{ $user->id }})"
                                                class="p-1.5 rounded-lg transition-colors {{ $user->email_verified_at ? 'text-gray-400 hover:text-amber-600 hover:bg-amber-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}"
                                                title="{{ $user->email_verified_at ? 'Deactivate' : 'Activate' }}">
                                                @if ($user->email_verified_at)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </button>
                                            <button wire:click="openDeleteModal({{ $user->id }})"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete User">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-16">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 mb-1">No users found</p>
                                        <p class="text-xs text-gray-500">Create a user or adjust your search and filters</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $users->links() }}
                </div>
            </div>

            {{-- Manage Roles Modal --}}
            @if ($showRolesModal)
                <div class="modal modal-open">
                    <div class="modal-box rounded-2xl max-w-4xl">
                        <h3 class="font-bold text-lg mb-4">Manage User Roles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-96 overflow-y-auto">
                            @foreach ($allRoles as $role)
                                <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                        class="checkbox checkbox-sm mt-0.5" style="accent-color:#982B55;">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $role->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $role->guard_name }} guard</p>
                                        @if ($role->permissions->count() > 0)
                                            <p class="text-xs" style="color:#982B55;">{{ $role->permissions->count() }} permissions</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="modal-action">
                            <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                            <button wire:click="updateUserRoles" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Update Roles</button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Delete Confirmation Modal --}}
            @if ($showDeleteModal)
                <div class="modal modal-open">
                    <div class="modal-box rounded-2xl">
                        <h3 class="font-bold text-lg mb-2">Delete User</h3>
                        <p class="text-sm text-gray-600 py-3">Are you sure you want to delete this user? This action cannot be undone and will remove all user data.</p>
                        <div class="flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 mb-4">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            This will permanently delete the user and all associated data.
                        </div>
                        <div class="modal-action">
                            <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                            <button wire:click="deleteUser" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete User</button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Manage Organization Modal --}}
            @if ($showOrganizationModal)
                <div class="modal modal-open">
                    <div class="modal-box rounded-2xl">
                        <h3 class="font-bold text-lg mb-4">Manage User Organization</h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Organization</label>
                            <select wire:model="selectedOrganization"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="">No Organization</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->legal_name }}{{ $org->code ? ' ('.$org->code.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-action">
                            <button wire:click="closeModals" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                            <button wire:click="updateUserOrganization" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#982B55;">Update Organization</button>
                        </div>
                    </div>
                </div>
            @endif

        @elseif ($activeTab === 'emails-phones')
            @livewire('email-phone-list')
        @endif

    </div>
</div>
