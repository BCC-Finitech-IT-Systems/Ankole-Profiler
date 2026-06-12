<div class="min-h-full py-6 px-4 md:px-8">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Organizations Mgt</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Organization Units</h1>
            </div>
            @can('create-units')
                <div class="flex items-center gap-2">
                    <a href="{{ route('organization-units.create') }}" class="btn btn-sm gap-1.5" style="background:#982B55;color:#fff;border-color:#982B55;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Organization Unit
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="w-full">
        <div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search units..." class="input input-bordered w-full pl-10">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="w-auto">
                            <select wire:model.live="perPage" class="select select-bordered">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-[11px]">
                        <thead class="bg-gray-50 text-[10px]">
                            <tr>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-80">UNIT NAME</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-32">CODE</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-40">DESCRIPTION</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-40">PARENT UNIT</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-32">STATUS</th>
                                <th class="px-1.5 py-0.5 text-left font-medium text-gray-500 uppercase tracking-wider w-20">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[11px]">
                            @forelse($organizationUnits as $unit)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-1.5 py-1 whitespace-nowrap">{{ $loop->iteration }}</td>
                                    <td class="px-1.5 py-1">{{ $unit->name }}</td>
                                    <td class="px-1.5 py-1">{{ $unit->code }}</td>
                                    <td class="px-1.5 py-1">{{ $unit->description }}</td>
                                    <td class="px-1.5 py-1">{{ $unit->parent_unit_id ? ($organizationUnits->find($unit->parent_unit_id)->name ?? '-') : '-' }}</td>
                                    <td class="px-1.5 py-1">
                                        @if($unit->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-1.5 py-1 whitespace-nowrap text-right font-medium">
                                        <div class="relative flex justify-end">
                                            <button wire:click="editUnit({{ $unit->id }})" class="p-1 rounded-full hover:bg-gray-200 focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <circle cx="10" cy="4" r="1.5"/>
                                                    <circle cx="10" cy="10" r="1.5"/>
                                                    <circle cx="10" cy="16" r="1.5"/>
                                                </svg>
                                            </button>
                                            <button wire:click="deleteUnit({{ $unit->id }})" class="p-1 rounded-full hover:bg-gray-200 focus:outline-none ml-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h14a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 4V4h4v2H8zm-2 2a1 1 0 011-1h6a1 1 0 011 1v10a1 1 0 01-1 1H7a1 1 0 01-1-1V8z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12">
                                        <div class="flex flex-col items-center gap-4">
                                            <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                            </svg>
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">No organization units found</h3>
                                                <p class="text-gray-500">Get started by creating your first organization unit.</p>
                                            </div>
                                            @can('create-units')
                                                <button class="btn btn-accent" wire:click="showCreateForm">
                                                    Create Organization Unit
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
