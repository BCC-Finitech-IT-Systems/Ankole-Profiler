<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Workplans</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Workplan Dashboard</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Total</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $total }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Completed</div>
            <div class="text-2xl font-semibold text-green-600">{{ $completed }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Ongoing</div>
            <div class="text-2xl font-semibold text-blue-600">{{ $ongoing }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Pending</div>
            <div class="text-2xl font-semibold text-gray-600">{{ $pending }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Overdue</div>
            <div class="text-2xl font-semibold text-red-600">{{ $overdueCount }}</div>
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
                <option value="">All Responsible People</option>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->given_name }} {{ $person->family_name }}</option>
                @endforeach
            </select>
            <select wire:model.live="quarterFilter" class="select select-bordered select-sm">
                <option value="">All Quarters</option>
                <option value="1">Q1</option>
                <option value="2">Q2</option>
                <option value="3">Q3</option>
                <option value="4">Q4</option>
            </select>
            <input type="text" wire:model.live.debounce.300ms="objectiveFilter" class="input input-bordered input-sm" placeholder="Strategic objective…" />
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Overdue Activities</h3>
        <table class="table table-sm w-full">
            <tbody class="divide-y divide-gray-100">
                @forelse($overdueActivities as $activity)
                    <tr>
                        <td class="px-2 py-2">{{ $activity->strategic_objective }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ $activity->workplan->department->name ?? '—' }}</td>
                        <td class="px-2 py-2 text-sm text-gray-500">{{ $activity->responsiblePerson?->full_name ?? $activity->responsible_team ?? '—' }}</td>
                        <td class="px-2 py-2 text-xs text-red-500">Due {{ $activity->end_date?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-2 py-4 text-center text-gray-400">No overdue activities.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
