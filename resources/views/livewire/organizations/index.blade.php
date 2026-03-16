<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Projects') }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">Manage Project hierarchy and structures oop</p>
            </div>

            @can('create-units')
                <div class="flex items-center gap-2">
                    <button wire:click="toggleFilters" class="btn btn-ghost btn-sm gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                        </svg>
                        Filters
                    </button>

                    <button class="btn btn-accent gap-2" disabled>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Organization
                    </button>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 h-[calc(100vh-8rem)] overflow-y-auto">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">

                {{-- Search and Filters --}}
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row gap-4">
                        {{-- Search Bar --}}
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="search"
                                    placeholder="Search organizations..." class="input input-bordered w-full pl-10">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Per Page Select --}}
                        <div class="w-auto">
                            <select wire:model.live="perPage" class="select select-bordered">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>

                    {{-- Advanced Filters --}}
                    @if ($showFilters)
                        <div class="mt-4 p-4 bg-base-100 rounded-lg border">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="label">
                                        <span class="label-text">Status</span>
                                    </label>
                                    <select wire:model.live="statusFilter" class="select select-bordered w-full">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="verified">Verified</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="label">
                                        <span class="label-text">Category</span>
                                    </label>
                                    <select wire:model.live="categoryFilter" class="select select-bordered w-full">
                                        <option value="">All Categories</option>
                                        @foreach ($categories as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <button wire:click="resetFilters" class="btn btn-ghost">
                                        Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Organizations Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-[11px]">
                        <thead class="bg-gray-50 text-[10px]">
                            <tr>
                                <th
                                    class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-16">
                                    #</th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-80">
                                    <button wire:click="sortBy('legal_name')"
                                        class="flex items-center gap-1 font-medium text-gray-500 hover:text-gray-700 uppercase text-[10px]">
                                        PROJECTS
                                        @if ($sortField === 'legal_name')
                                            <svg class="w-2.5 h-2.5 {{ $sortDirection === 'asc' ? 'rotate-0' : 'rotate-180' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-32">
                                    <button wire:click="sortBy('category')"
                                        class="flex items-center gap-1 font-medium text-gray-500 hover:text-gray-700 uppercase text-[10px]">
                                        CATEGORY
                                        @if ($sortField === 'category')
                                            <svg class="w-2.5 h-2.5 {{ $sortDirection === 'asc' ? 'rotate-0' : 'rotate-180' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-small text-gray-500 uppercase tracking-wider w-40">
                                    LOCATION</th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-small text-gray-500 uppercase tracking-wider w-64">
                                    PRIMARY
                                    CONTACT </th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-small text-gray-500 uppercase tracking-wider w-32">
                                    <button wire:click="sortBy('is_active')"
                                        class="flex items-center gap-1 font-small text-gray-500 hover:text-gray-700 uppercase text-[10px]">
                                        STATUS
                                        @if ($sortField === 'is_active')
                                            <svg class="w-2.5 h-2.5 {{ $sortDirection === 'asc' ? 'rotate-0' : 'rotate-180' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-24">
                                    <button wire:click="sortBy('created_at')"
                                        class="flex items-center gap-1 font-medium text-gray-500 hover:text-gray-700 uppercase text-[10px]">
                                        CREATED
                                        @if ($sortField === 'created_at')
                                            <svg class="w-2.5 h-2.5 {{ $sortDirection === 'asc' ? 'rotate-0' : 'rotate-180' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th
                                    class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-20">
                                    ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[11px]">
                            @forelse($organizations as $organization)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-1.5 py-1 whitespace-nowrap">
                                        {{ $organizations->firstItem() + $loop->index }}</td>
                                    <td class="px-1.5 py-1">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div
                                                    class="h-10 w-10 rounded-full bg-accent/10 flex items-center justify-center">
                                                    <span class="text-accent font-medium text-sm">
                                                        {{ substr($organization->legal_name, 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4 min-w-0 flex-1">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <a href="{{ route('organizations.show', $organization->id) }}"
                                                        class="hover:text-primary-focus hover:underline transition-colors">
                                                        {{ $organization->legal_name }}
                                                    </a>
                                                </div>

                                                @if ($organization->code)
                                                    <div class="text-xs text-gray-400">Code: {{ $organization->code }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-1.5 py-1 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $organization->category }}
                                        </span>
                                    </td>
                                    <td class="px-1.5 py-1">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $organization->city }}</div>
                                            @if ($organization->district)
                                                <div class="text-gray-500">{{ $organization->district ?: 'Mbarara' }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-1.5 py-1">
                                        <div class="text-sm text-gray-900">
                                            @if ($organization->primary_contact_name)
                                                <div class="font-medium truncate">
                                                    {{ $organization->primary_contact_name }}</div>
                                            @endif
                                            @if ($organization->contact_email)
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-gray-400 flex-shrink-0"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z">
                                                        </path>
                                                        <path
                                                            d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z">
                                                        </path>
                                                    </svg>
                                                    <span class="truncate">{{ $organization->contact_email }}</span>
                                                </div>
                                            @endif
                                            @if ($organization->contact_phone)
                                                <div class="flex items-center mt-1">
                                                    <svg class="w-4 h-4 mr-1 text-gray-400 flex-shrink-0"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z">
                                                        </path>
                                                    </svg>
                                                    <span class="truncate">{{ $organization->contact_phone }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-1.5 py-1 whitespace-nowrap">
                                        @if ($organization->is_active || $organization->is_verified || $organization->is_trial)
                                            <div class="flex flex-wrap gap-1">
                                                @if ($organization->is_active)
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                                @endif
                                                @if ($organization->is_verified)
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Verified</span>
                                                @endif

                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">None</span>
                                        @endif
                                    </td>
                                    <td class="px-1.5 py-1 whitespace-nowrap">
                                        <div class="text-xs text-gray-500">
                                            {{ $organization->created_at->format('M j, Y') }}
                                        </div>
                                    </td>
                                    <td class="px-1.5 py-1 whitespace-nowrap text-right font-medium">
                                        <div x-data="{ open: false }" class="relative flex justify-end">
                                            <button @click="open = !open"
                                                class="p-1 rounded-full hover:bg-gray-200 focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <circle cx="10" cy="4" r="1.5" />
                                                    <circle cx="10" cy="10" r="1.5" />
                                                    <circle cx="10" cy="16" r="1.5" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false"
                                                class="absolute right-0 mt-2 w-32 bg-white border border-gray-200 rounded shadow-lg z-10">
                                                <ul class="py-1">
                                                    <li>
                                                        <a href="{{ route('organizations.show', $organization->id) }}"
                                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 block">View</a>
                                                    </li>
                                                    <li>
                                                        <button
                                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Edit</button>
                                                    </li>
                                                    @can('manage-sites')
                                                    <li>
                                                        <button
                                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">Manage
                                                            Sites</button>
                                                    </li>
                                                    @endcan
                                                    <li>
                                                        <button
                                                            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-red-600"
                                                            wire:click="confirmDelete({{ $organization->id }})">
                                                            Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                                @if ($confirmingDeleteId)
                                                    <div
                                                        class="fixed inset-0 flex items-center justify-center z-50 bg-transparent">
                                                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
                                                            <h2 class="text-lg font-semibold mb-4">Delete Organization
                                                            </h2>
                                                            <p class="mb-4">Are you sure you want to delete this
                                                                organization? This action cannot be undone.</p>
                                                            <div class="flex justify-end gap-2">
                                                                <button wire:click="$set('confirmingDeleteId', null)"
                                                                    class="btn btn-ghost">Cancel</button>
                                                                <button wire:click="deleteOrganization"
                                                                    class="btn btn-error">Delete</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12">
                                        <div class="flex flex-col items-center gap-4">
                                            <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                            </svg>
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">No organizations found
                                                </h3>
                                                <p class="text-gray-500">
                                                    @if ($search)
                                                        No organizations match your search criteria.
                                                    @else
                                                        Get started by creating your first organization.
                                                    @endif
                                                </p>
                                            </div>
                                            @can('create-organizations')
                                                @if (!$search)
                                                    <button class="btn btn-accent" disabled>
                                                        Create Organization (Coming Soon)
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($organizations->hasPages())
                    <div class="bg-gray-50 px-6 py-3">
                        {{ $organizations->links() }}
                    </div>
                @endif

                {{-- Results Summary --}}
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between text-sm text-gray-700">
                        <div>
                            Showing {{ $organizations->firstItem() ?? 0 }} to {{ $organizations->lastItem() ?? 0 }}
                            of {{ $organizations->total() }} organizations
                        </div>
                        <div class="flex items-center gap-4">
                            @if ($search)
                                <span class="text-gray-500">
                                    Filtered by: "{{ $search }}"
                                </span>
                            @endif
                            @if ($statusFilter)
                                <span class="text-gray-500">
                                    Status: {{ ucfirst($statusFilter) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
