<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Workplans</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">{{ $workplan->title }} (v{{ $workplan->version_number }})</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge">{{ str_replace('_', ' ', $workplan->status) }}</span>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @error('decision_comment')
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ $message }}</div>
    @enderror

    {{-- Workflow actions --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap items-center gap-2">
        @if($workplan->status === 'draft')
            @can('submit-workplans')
                <input type="text" wire:model="review_comment" class="input input-bordered input-sm flex-1 min-w-[200px]" placeholder="Submission note (optional)" />
                <button wire:click="submit" type="button" class="btn btn-sm border-0 text-white" style="background:#982B55;">Submit for Approval</button>
            @endcan
        @endif

        @if($workplan->status === 'submitted')
            @can('approve-workplans')
                <input type="text" wire:model="decision_comment" class="input input-bordered input-sm flex-1 min-w-[200px]" placeholder="Decision comment" />
                <button wire:click="approve" type="button" class="btn btn-sm border-0 text-white" style="background:#982B55;">Approve</button>
                <button wire:click="reject" type="button" class="btn btn-sm btn-outline">Reject</button>
            @endcan
        @endif

        @can('create-workplans')
            @if(!in_array($workplan->status, ['draft', 'cancelled']))
                <button wire:click="createRevision" type="button" class="btn btn-sm btn-outline">Create Revision</button>
            @endif
        @endcan

        @can('carry-forward-workplans')
            @if(in_array($workplan->status, ['approved', 'in_progress', 'completed', 'deferred']))
                <button wire:click="carryForward" type="button" wire:confirm="Carry unfinished activities into FY{{ $workplan->year + 1 }}?" class="btn btn-sm btn-outline">
                    Carry Forward to FY{{ $workplan->year + 1 }}
                </button>
            @endif
        @endcan
    </div>

    @if($workplan->decision_comment)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-sm text-gray-600">
            <span class="font-medium text-gray-700">Latest decision comment:</span> {{ $workplan->decision_comment }}
        </div>
    @endif

    {{-- Activities --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Activities</h3>
            @can('edit-workplans')
                @if($workplan->isEditable())
                    <button wire:click="openActivityModal" type="button" class="btn btn-sm btn-outline">Add Activity</button>
                @endif
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Objective / Activity</th>
                        <th class="px-3 py-2 text-left">Responsible</th>
                        <th class="px-3 py-2 text-left">Deadline</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">% Complete</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($activities as $act)
                        <tr class="{{ $act->isOverdue() ? 'bg-red-50' : '' }}">
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-800">{{ $act->strategic_objective }}</div>
                                <div class="text-xs text-gray-500">{{ $act->activity }}</div>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $act->responsiblePerson?->full_name ?? $act->responsible_team ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs">
                                {{ $act->end_date?->format('d M Y') ?? '—' }}
                                @if($act->isOverdue())
                                    <span class="badge badge-sm badge-error ml-1">Overdue</span>
                                @endif
                            </td>
                            <td class="px-3 py-2"><span class="badge badge-sm">{{ str_replace('_', ' ', $act->status) }}</span></td>
                            <td class="px-3 py-2 text-xs">{{ $act->percent_complete }}%</td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex justify-end gap-1">
                                    @can('record-workplan-progress')
                                        @if(in_array($workplan->status, ['approved', 'in_progress']))
                                            <button wire:click="openProgressForm({{ $act->id }})" type="button" class="btn btn-ghost btn-xs">Record Progress</button>
                                        @endif
                                    @endcan
                                    @can('edit-workplans')
                                        @if($workplan->isEditable())
                                            <button wire:click="openActivityModal({{ $act->id }})" type="button" class="btn btn-ghost btn-xs">Edit</button>
                                            <button wire:click="removeActivity({{ $act->id }})" type="button" wire:confirm="Remove this activity?" class="btn btn-ghost btn-xs text-red-500">Remove</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No activities yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

    {{-- Activity modal --}}
    @if($showActivityModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeActivityModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">{{ $editingActivityId ? 'Edit Activity' : 'New Activity' }}</h2>
                    <button wire:click="closeActivityModal" type="button" class="btn btn-ghost btn-sm btn-square">✕</button>
                </div>
                <form wire:submit="saveActivity" class="p-5 space-y-3">
                    <div>
                        <label class="label label-text text-xs font-medium">Strategic Objective <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="strategic_objective" class="input input-bordered w-full" />
                        @error('strategic_objective') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Activity <span class="text-red-500">*</span></label>
                        <textarea wire:model="activity" class="textarea textarea-bordered w-full" rows="2"></textarea>
                        @error('activity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Expected Output</label>
                            <input type="text" wire:model="expected_output" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Performance Indicator</label>
                            <input type="text" wire:model="performance_indicator" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Baseline</label>
                            <input type="text" wire:model="baseline" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Target</label>
                            <input type="text" wire:model="target" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Start Date</label>
                            <input type="date" wire:model="start_date" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Deadline</label>
                            <input type="date" wire:model="end_date" class="input input-bordered w-full" />
                            @error('end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Priority</label>
                            <select wire:model="priority" class="select select-bordered w-full">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Budget Estimate</label>
                            <input type="number" step="0.01" wire:model="budget_estimate" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Funding Source</label>
                            <input type="text" wire:model="funding_source" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Responsible Team</label>
                            <input type="text" wire:model="responsible_team" class="input input-bordered w-full" />
                        </div>
                    </div>
                    <div class="relative">
                        <label class="label label-text text-xs font-medium">Responsible Person</label>
                        <input type="text" wire:model.live.debounce.300ms="responsiblePersonSearch" class="input input-bordered w-full" placeholder="Search by name…" />
                        @if(count($personResults) > 0)
                            <ul class="mt-1 border border-gray-200 rounded-lg shadow bg-white text-sm max-h-36 overflow-y-auto">
                                @foreach($personResults as $person)
                                    <li>
                                        <button type="button" wire:click="selectResponsiblePerson({{ $person['id'] }}, '{{ addslashes(($person['given_name'] ?? '').' '.($person['family_name'] ?? '')) }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50">
                                            {{ $person['given_name'] }} {{ $person['family_name'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Dependencies</label>
                        <textarea wire:model="dependencies" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeActivityModal" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Progress modal --}}
    @if($activeProgressActivityId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeProgressForm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Record Progress</h2>
                <form wire:submit="recordProgress" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Reported On</label>
                            <input type="date" wire:model="progress_reported_on" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">% Complete</label>
                            <input type="number" min="0" max="100" wire:model="progress_percent_complete" class="input input-bordered w-full" />
                        </div>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Status</label>
                        <select wire:model="progressStatus" class="select select-bordered w-full">
                            <option value="not_started">Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="deferred">Deferred</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Work Completed</label>
                        <textarea wire:model="work_completed" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Pending Work</label>
                        <textarea wire:model="pending_work" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Challenges</label>
                        <textarea wire:model="challenges" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Corrective Action</label>
                        <textarea wire:model="corrective_action" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Expenditure</label>
                        <input type="number" step="0.01" wire:model="expenditure" class="input input-bordered w-full" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" wire:click="closeProgressForm" class="btn btn-ghost btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-sm border-0 text-white" style="background:#982B55;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
