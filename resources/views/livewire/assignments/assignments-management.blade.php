<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Assignments</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Assignment Tracking</h1>
        </div>
        @can('create-assignments')
            <button wire:click="create" type="button" class="btn btn-sm border-0 text-white gap-1.5" style="background:#982B55;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Assignment
            </button>
        @endcan
    </div>

<div class="py-6 px-4 md:px-8">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @error('responsible_person_id') <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ $message }}</div> @enderror

    <div class="w-full space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex flex-wrap gap-3">
                <select wire:model.live="statusFilter" class="select select-bordered select-sm">
                    <option value="">All Statuses</option>
                    <option value="not_started">Not Started</option>
                    <option value="in_progress">In Progress</option>
                    <option value="blocked">Blocked</option>
                    <option value="awaiting_review">Awaiting Review</option>
                    <option value="completed">Completed</option>
                    <option value="deferred">Deferred</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select wire:model.live="priorityFilter" class="select select-bordered select-sm">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
                <select wire:model.live="departmentFilter" class="select select-bordered select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="overdueOnly" class="checkbox checkbox-sm" /> Overdue only
                </label>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if($assignments->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <p class="text-gray-500 font-medium">No assignments found</p>
                    @can('create-assignments')
                        <button wire:click="create" type="button" class="btn btn-sm mt-4 border-0 text-white" style="background:#982B55;">Create First Assignment</button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Title</th>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">Lead</th>
                                <th class="px-4 py-3 text-left">Priority</th>
                                <th class="px-4 py-3 text-left">Due</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($assignments as $assignment)
                                <tr class="hover:bg-gray-50 {{ $assignment->isOverdue() ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('assignments.show', $assignment) }}" class="font-medium text-gray-900 hover:underline">{{ $assignment->title }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->responsiblePerson?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-3"><span class="badge badge-sm">{{ ucfirst($assignment->priority) }}</span></td>
                                    <td class="px-4 py-3 text-xs">
                                        {{ $assignment->due_date?->format('d M Y') ?? '—' }}
                                        @if($assignment->isOverdue())<span class="badge badge-sm badge-error ml-1">Overdue</span>@endif
                                    </td>
                                    <td class="px-4 py-3"><span class="badge badge-sm">{{ str_replace('_', ' ', $assignment->status) }}</span></td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-ghost btn-xs">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $assignments->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>

    {{-- Create / Edit modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">{{ $editingId ? 'Edit Assignment' : 'New Assignment' }}</h2>
                    <button wire:click="closeModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>
                <form wire:submit="save" class="p-5 space-y-3">
                    <div>
                        <label class="label label-text text-xs font-medium">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" class="input input-bordered w-full" />
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Description</label>
                        <textarea wire:model="description" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Organization <span class="text-red-500">*</span></label>
                            <select wire:model.live="organization_id" class="select select-bordered w-full">
                                <option value="">— Select —</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->display_name ?? $org->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('organization_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Department</label>
                            <select wire:model="department_id" class="select select-bordered w-full">
                                <option value="">— None —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Priority</label>
                            <select wire:model="priority" class="select select-bordered w-full">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Category</label>
                            <input type="text" wire:model="category" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Start Date</label>
                            <input type="date" wire:model="start_date" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Due Date</label>
                            <input type="date" wire:model="due_date" class="input input-bordered w-full" />
                            @error('due_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="relative">
                        <label class="label label-text text-xs font-medium">Lead (Responsible Person)</label>
                        <input type="text" wire:model.live.debounce.300ms="leadSearch" class="input input-bordered w-full" placeholder="Search by name…" />
                        @if(count($leadResults) > 0)
                            <ul class="mt-1 border border-gray-200 rounded-lg shadow bg-white text-sm max-h-36 overflow-y-auto">
                                @foreach($leadResults as $person)
                                    <li>
                                        <button type="button" wire:click="selectLead({{ $person['id'] }}, '{{ addslashes(($person['given_name'] ?? '').' '.($person['family_name'] ?? '')) }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50">
                                            {{ $person['given_name'] }} {{ $person['family_name'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <label class="label label-text text-xs font-medium">Expected Result</label>
                        <textarea wire:model="expected_result" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Dependencies</label>
                        <textarea wire:model="dependencies" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
