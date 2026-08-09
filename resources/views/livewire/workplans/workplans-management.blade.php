<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Workplans</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Annual Departmental Workplans</h1>
        </div>
        @can('create-workplans')
            <button wire:click="create" type="button"
                    class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Workplan
            </button>
        @endcan
    </div>

<div class="py-6 px-4 md:px-8">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="w-full space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="yearFilter" class="select select-bordered w-full sm:w-40">
                    <option value="">All Years</option>
                    @for ($y = now()->year + 1; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <select wire:model.live="statusFilter" class="select select-bordered w-full sm:w-48">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="deferred">Deferred</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select wire:model.live="departmentFilter" class="select select-bordered w-full sm:w-52">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($workplans->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <p class="text-gray-500 font-medium">No workplans found</p>
                    @can('create-workplans')
                        <button wire:click="create" type="button" class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">
                            Create First Workplan
                        </button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">Year</th>
                                <th class="px-4 py-3 text-left">Version</th>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($workplans as $workplan)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $workplan->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $workplan->year }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">v{{ $workplan->version_number }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('workplans.show', $workplan) }}" class="font-medium text-gray-900 hover:underline">
                                            {{ $workplan->title }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge badge-sm">{{ str_replace('_', ' ', $workplan->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('workplans.show', $workplan) }}" class="btn btn-ghost btn-xs">View</a>
                                            @can('archive-workplans')
                                                @if(!in_array($workplan->status, ['cancelled']))
                                                    <button wire:click="confirmArchive({{ $workplan->id }})" type="button" class="btn btn-ghost btn-xs text-red-500">Cancel</button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($workplans->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $workplans->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>

    {{-- Create modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">New Workplan</h2>
                    <button wire:click="closeModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>
                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label label-text text-xs font-medium">Department <span class="text-red-500">*</span></label>
                        <select wire:model="department_id" class="select select-bordered w-full">
                            <option value="">— Select —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Year <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="year" class="input input-bordered w-full" />
                        @error('year') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Title</label>
                        <input type="text" wire:model="title" class="input input-bordered w-full" placeholder="e.g. FY2026 Workplan" />
                        @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Create</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Cancel confirmation --}}
    @if($confirmingArchiveId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Cancel Workplan?</h3>
                <div class="flex justify-center gap-3 mt-4">
                    <button wire:click="cancelArchive" type="button" class="btn btn-ghost btn-sm">Back</button>
                    <button wire:click="archive" type="button" class="btn btn-sm bg-red-500 hover:bg-red-600 border-0 text-white">Cancel Workplan</button>
                </div>
            </div>
        </div>
    @endif

</div>
