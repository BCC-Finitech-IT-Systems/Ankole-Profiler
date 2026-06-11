<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Organization Units') }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">Manage organization unit hierarchy and structures</p>
            </div>
            @can('create-units')
                <button class="btn btn-accent gap-2" wire:click="showCreateForm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Unit
                </button>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 h-[calc(100vh-8rem)] overflow-y-auto">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden border border-gray-200 sm:rounded-lg">
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

                @if($showCreateForm)
                    <div class="mb-6 p-4 border rounded bg-gray-50">
                        <h3 class="font-semibold mb-2">Create Organization Unit</h3>
                        <form wire:submit.prevent="createUnit">
                            <div class="mb-2">
                                <label class="block text-sm">Name</label>
                                <input type="text" wire:model="createForm.name" class="border rounded px-2 py-1 w-full" required>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm">Code</label>
                                <input type="text" wire:model="createForm.code" class="border rounded px-2 py-1 w-full" required>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm">Description</label>
                                <textarea wire:model="createForm.description" class="border rounded px-2 py-1 w-full"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm">Parent Unit</label>
                                <select wire:model="createForm.parent_unit_id" class="border rounded px-2 py-1 w-full">
                                    <option value="">None</option>
                                    @foreach($organizationUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm">Active</label>
                                <input type="checkbox" wire:model="createForm.is_active">
                            </div>
                            <button type="submit" class="bg-accent text-white px-4 py-2 rounded">Create</button>
                            <button type="button" wire:click="$set('showCreateForm', false)" class="ml-2 px-4 py-2 rounded border">Cancel</button>
                        </form>
                    </div>
                @endif

                <!-- Modal for creating organization unit -->
                <div x-data="{ open: @entangle('showCreateForm').defer }">
                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
                            <button @click="open = false; $wire.set('showCreateForm', false)" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <h3 class="font-semibold mb-4 text-lg">Create Organization Unit</h3>
                            <form wire:submit.prevent="createUnit">
                                <div class="mb-3">
                                    <label class="block text-sm">Name</label>
                                    <input type="text" wire:model="createForm.name" class="border rounded px-2 py-1 w-full" required>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm">Code</label>
                                    <input type="text" wire:model="createForm.code" class="border rounded px-2 py-1 w-full" required>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm">Description</label>
                                    <textarea wire:model="createForm.description" class="border rounded px-2 py-1 w-full"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm">Parent Unit</label>
                                    <select wire:model="createForm.parent_unit_id" class="border rounded px-2 py-1 w-full">
                                        <option value="">None</option>
                                        @foreach($organizationUnits as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm">Active</label>
                                    <input type="checkbox" wire:model="createForm.is_active">
                                </div>
                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="button" @click="open = false; $wire.set('showCreateForm', false)" class="px-4 py-2 rounded border">Cancel</button>
                                    <button type="submit" class="bg-accent text-white px-4 py-2 rounded">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-2">
                    @can('create-units')
                        <button class="btn btn-accent gap-2" wire:click="showCreateForm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Organization Unit
                        </button>
                    @endcan
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
