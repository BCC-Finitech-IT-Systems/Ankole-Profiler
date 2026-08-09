<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Assignments</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">{{ $assignment->title }}</h1>
        </div>
        <span class="badge">{{ str_replace('_', ' ', $assignment->status) }}</span>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-sm text-gray-600">{{ $assignment->description ?? 'No description.' }}</p>
                <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                    <div><span class="text-gray-400">Priority:</span> {{ ucfirst($assignment->priority) }}</div>
                    <div><span class="text-gray-400">Lead:</span> {{ $assignment->responsiblePerson?->full_name ?? '—' }}</div>
                    <div><span class="text-gray-400">Due:</span> {{ ($assignment->revised_due_date ?? $assignment->due_date)?->format('d M Y') ?? '—' }}
                        @if($assignment->isOverdue())<span class="badge badge-sm badge-error ml-1">Overdue</span>@endif
                    </div>
                    <div><span class="text-gray-400">% Complete:</span> {{ $assignment->percent_complete }}%</div>
                </div>
                @if($assignment->review_comment)
                    <div class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-600">
                        <span class="font-medium">Latest review comment:</span> {{ $assignment->review_comment }}
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-wrap gap-2">
                @can('reportProgress', $assignment)
                    <button wire:click="openProgressForm" type="button" class="btn btn-sm border-0 text-white" style="background:#982B55;">Report Progress</button>
                @endcan

                @can('review', $assignment)
                    <input type="text" wire:model="review_comment" class="input input-bordered input-sm flex-1 min-w-[180px]" placeholder="Review comment" />
                    <button wire:click="accept" type="button" class="btn btn-sm btn-outline">Accept</button>
                    <button wire:click="returnForRevision" type="button" class="btn btn-sm btn-outline">Return</button>
                    <select wire:model="closeStatus" class="select select-bordered select-sm">
                        <option value="cancelled">Cancel</option>
                        <option value="deferred">Defer</option>
                    </select>
                    <button wire:click="close" type="button" class="btn btn-sm btn-outline text-red-500">Close</button>
                @endcan
            </div>
            @error('review_comment') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            {{-- Progress history --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Progress History</h3>
                @forelse($progressUpdates as $update)
                    <div class="border-b border-gray-100 py-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $update->reported_on->format('d M Y') }}</span>
                            <span class="text-xs text-gray-400">{{ $update->percent_complete }}%</span>
                        </div>
                        @if($update->notes)<p class="text-gray-600 mt-1">{{ $update->notes }}</p>@endif
                        @if($update->blockers)<p class="text-red-500 text-xs mt-1">Blockers: {{ $update->blockers }}</p>@endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No progress recorded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Support Assignees</h3>
                @forelse($supportPeople as $p)
                    <div class="text-sm text-gray-600">{{ $p->full_name }}</div>
                @empty
                    <p class="text-sm text-gray-400">None</p>
                @endforelse
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Watchers</h3>
                @forelse($watchers as $p)
                    <div class="text-sm text-gray-600">{{ $p->full_name }}</div>
                @empty
                    <p class="text-sm text-gray-400">None</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

    {{-- Progress modal --}}
    @if($showProgressForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeProgressForm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-800">Report Progress</h2>
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
                            <option value="blocked">Blocked</option>
                            <option value="awaiting_review">Submit for Review</option>
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Notes</label>
                        <textarea wire:model="notes" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Blockers</label>
                        <textarea wire:model="blockers" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Next Steps</label>
                        <textarea wire:model="next_steps" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label label-text text-xs font-medium">Time Spent (minutes)</label>
                            <input type="number" min="0" wire:model="time_spent_minutes" class="input input-bordered w-full" />
                        </div>
                        <div>
                            <label class="label label-text text-xs font-medium">Revised Due Date</label>
                            <input type="date" wire:model="revised_due_date" class="input input-bordered w-full" />
                        </div>
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
