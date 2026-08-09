<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Policies</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Adoption Dashboard</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Adoption Coverage</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $coveragePercent }}%</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Overdue</div>
            <div class="text-2xl font-semibold text-red-600">{{ $overdueCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Pending Exceptions</div>
            <div class="text-2xl font-semibold text-amber-600">{{ $pendingExceptionsCount }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="policyFilter" class="select select-bordered select-sm">
                <option value="">All Policies</option>
                @foreach($policies as $p)
                    <option value="{{ $p->id }}">{{ $p->title }}</option>
                @endforeach
            </select>
            <select wire:model.live="institutionFilter" class="select select-bordered select-sm">
                <option value="">All Institutions</option>
                @foreach($institutions as $inst)
                    <option value="{{ $inst->id }}">{{ $inst->display_name ?? $inst->legal_name }}</option>
                @endforeach
            </select>
            <select wire:model.live="departmentFilter" class="select select-bordered select-sm">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="select select-bordered select-sm">
                <option value="">All Statuses</option>
                <option value="sent">Sent</option>
                <option value="acknowledged">Acknowledged</option>
                <option value="adopted">Adopted</option>
                <option value="partially_adopted">Partially Adopted</option>
                <option value="exception_requested">Exception Requested</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
    </div>

    @if($exceptions->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Pending Exceptions</h3>
            <table class="table table-sm w-full">
                <tbody class="divide-y divide-gray-100">
                    @foreach($exceptions as $exception)
                        <tr>
                            <td class="px-2 py-2">{{ $exception->policy->title }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ $exception->organization->display_name ?? $exception->organization->legal_name }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ $exception->exception_reason }}</td>
                            <td class="px-2 py-2 text-right">
                                @can('decide-policy-exceptions')
                                    <button wire:click="approveException({{ $exception->id }})" type="button" class="btn btn-ghost btn-xs text-green-600">Approve</button>
                                    <button wire:click="rejectException({{ $exception->id }})" type="button" class="btn btn-ghost btn-xs text-red-500">Reject</button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="table table-sm w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Policy</th>
                    <th class="px-4 py-3 text-left">Institution</th>
                    <th class="px-4 py-3 text-left">Due Date</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($publications as $publication)
                    <tr>
                        <td class="px-4 py-3">{{ $publication->policy->title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $publication->organization->display_name ?? $publication->organization->legal_name }}</td>
                        <td class="px-4 py-3 text-xs">{{ $publication->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="badge badge-sm">{{ str_replace('_', ' ', $publication->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No publications match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($publications->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $publications->links() }}</div>
        @endif
    </div>
</div>
</div>
