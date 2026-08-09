<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Assignments</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Assignment Dashboard</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Total</div>
            <div class="text-xl font-semibold text-gray-800">{{ $total }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Not Started</div>
            <div class="text-xl font-semibold text-gray-600">{{ $notStarted }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">In Progress</div>
            <div class="text-xl font-semibold text-blue-600">{{ $inProgress }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Blocked</div>
            <div class="text-xl font-semibold text-orange-600">{{ $blocked }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Awaiting Review</div>
            <div class="text-xl font-semibold text-purple-600">{{ $awaitingReview }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Completed</div>
            <div class="text-xl font-semibold text-green-600">{{ $completed }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Overdue</div>
            <div class="text-xl font-semibold text-red-600">{{ $overdueCount }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="departmentFilter" class="select select-bordered select-sm">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="responsiblePersonFilter" class="select select-bordered select-sm">
                <option value="">All People</option>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->given_name }} {{ $person->family_name }}</option>
                @endforeach
            </select>
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
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Overdue Assignments</h3>
        <table class="table table-sm w-full">
            <tbody class="divide-y divide-gray-100">
                @forelse($overdueAssignments as $assignment)
                    <tr>
                        <td class="px-2 py-2">{{ $assignment->title }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ $assignment->department?->name ?? '—' }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ $assignment->responsiblePerson?->full_name ?? '—' }}</td>
                        <td class="px-2 py-2 text-xs text-red-500">Due {{ ($assignment->revised_due_date ?? $assignment->due_date)?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-2 py-4 text-center text-gray-400">No overdue assignments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
