<div class="min-h-full py-6 px-4 md:px-8">

    {{-- Page header --}}
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Projects</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Project Management</h1>
            </div>
            @can('create-projects')
                <button wire:click="create" type="button"
                        class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Project
                </button>
            @endcan
        </div>
    </x-slot>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="w-full space-y-4">

        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by name or code…"
                       class="input input-bordered flex-1" />

                <select wire:model.live="statusFilter" class="select select-bordered w-full sm:w-40">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select wire:model.live="departmentFilter" class="select select-bordered w-full sm:w-52">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div wire:loading.flex wire:target="search,statusFilter,departmentFilter"
                 class="items-center gap-2 mt-3 text-sm text-rose-600">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Loading…</span>
            </div>
        </div>

        {{-- Projects table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($projects->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h1m4 0h1M9 16h1"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No projects found</p>
                    <p class="text-sm text-gray-400 mt-1">
                        @if($search || $statusFilter || $departmentFilter)
                            Try adjusting your search or filters.
                        @else
                            Get started by creating the first project.
                        @endif
                    </p>
                    @can('create-projects')
                        @if(!$search && !$statusFilter && !$departmentFilter)
                            <button wire:click="create" type="button"
                                    class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">
                                Create First Project
                            </button>
                        @endif
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Code</th>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">Organization</th>
                                <th class="px-4 py-3 text-left">Dates</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($projects as $project)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $project->name }}</div>
                                        @if($project->description)
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $project->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                        {{ $project->code ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $project->department?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $project->department?->organization?->display_name
                                            ?? $project->department?->organization?->legal_name
                                            ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        @if($project->starts_on || $project->ends_on)
                                            {{ $project->starts_on?->format('d M Y') ?? '?' }}
                                            –
                                            {{ $project->ends_on?->format('d M Y') ?? '?' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge badge-sm {{ $project->is_active ? 'badge-success' : 'badge-ghost' }}">
                                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('projects.persons', $project) }}"
                                               class="btn btn-ghost btn-xs" title="View persons">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </a>
                                            @can('edit-projects')
                                                <button wire:click="edit({{ $project->id }})" type="button"
                                                        class="btn btn-ghost btn-xs" title="Edit project">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                            @endcan
                                            @can('delete-projects')
                                                <button wire:click="confirmDelete({{ $project->id }})" type="button"
                                                        class="btn btn-ghost btn-xs text-red-500" title="Delete project">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($projects->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $projects->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Create / Edit modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
             wire:click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ $editingId ? 'Edit Project' : 'New Project' }}
                    </h2>
                    <button wire:click="closeModal" type="button" class="btn btn-ghost btn-sm btn-square">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">

                    {{-- Name --}}
                    <div>
                        <label class="label label-text text-xs font-medium">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="input input-bordered w-full"
                               placeholder="Project name" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Code + Department --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Code</label>
                            <input type="text" wire:model="code" class="input input-bordered w-full"
                                   placeholder="e.g. PROJ-001" />
                            @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Department <span class="text-red-500">*</span></label>
                            <select wire:model.live="department_id" class="select select-bordered w-full">
                                <option value="">— Select —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Organization unit --}}
                    @if(count($availableUnits) > 0)
                        <div>
                            <label class="label label-text text-xs font-medium">Organization Unit</label>
                            <select wire:model="organization_unit_id" class="select select-bordered w-full">
                                <option value="">— None —</option>
                                @foreach($availableUnits as $unit)
                                    <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                                @endforeach
                            </select>
                            @error('organization_unit_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    {{-- Description --}}
                    <div>
                        <label class="label label-text text-xs font-medium">Description</label>
                        <textarea wire:model="description" class="textarea textarea-bordered w-full" rows="2"
                                  placeholder="Optional description"></textarea>
                    </div>

                    {{-- Client person search --}}
                    <div>
                        <label class="label label-text text-xs font-medium">Client Person</label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="clientSearch"
                                   class="input input-bordered w-full pr-8"
                                   placeholder="Search by name or person ID…" />
                            @if($client_person_id)
                                <button type="button" wire:click="clearClient"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        @if(count($clientResults) > 0)
                            <ul class="mt-1 border border-gray-200 rounded-lg shadow bg-white text-sm max-h-36 overflow-y-auto">
                                @foreach($clientResults as $person)
                                    <li>
                                        <button type="button" wire:click="selectClient({{ $person['id'] }}, '{{ addslashes(($person['given_name'] ?? '') . ' ' . ($person['family_name'] ?? '')) }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50">
                                            {{ $person['given_name'] }} {{ $person['family_name'] }}
                                            <span class="text-gray-400 text-xs ml-1">{{ $person['person_id'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @error('client_person_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- External client (shown only when no person selected) --}}
                    @if(!$client_person_id)
                        <div>
                            <label class="label label-text text-xs font-medium">External Client Name</label>
                            <input type="text" wire:model="external_client_name" class="input input-bordered w-full"
                                   placeholder="If client is not in the system" />
                            @error('external_client_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Start Date</label>
                            <input type="date" wire:model="starts_on" class="input input-bordered w-full" />
                            @error('starts_on') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">End Date</label>
                            <input type="date" wire:model="ends_on" class="input input-bordered w-full" />
                            @error('ends_on') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Active toggle --}}
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="is_active" class="toggle toggle-sm" id="project-active" />
                        <label for="project-active" class="text-sm text-gray-700">Active</label>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;"
                                wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete confirmation modal --}}
    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <svg class="w-10 h-10 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <h3 class="text-base font-semibold text-gray-800 mb-1">Delete Project?</h3>
                <p class="text-sm text-gray-500 mb-5">This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="cancelDelete" type="button" class="btn btn-ghost btn-sm">Cancel</button>
                    <button wire:click="delete" type="button"
                            class="btn btn-sm bg-red-500 hover:bg-red-600 border-0 text-white"
                            wire:loading.attr="disabled" wire:target="delete">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
