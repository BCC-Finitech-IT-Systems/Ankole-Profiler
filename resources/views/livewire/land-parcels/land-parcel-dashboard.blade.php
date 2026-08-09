<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Land Registration</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Land Registration Dashboard</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Total</div>
            <div class="text-xl font-semibold text-gray-800">{{ $total }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Untitled</div>
            <div class="text-xl font-semibold text-gray-600">{{ $untitledCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Delayed</div>
            <div class="text-xl font-semibold text-orange-600">{{ $delayedCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Disputed</div>
            <div class="text-xl font-semibold text-red-600">{{ $disputedCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Expiring Leases</div>
            <div class="text-xl font-semibold text-amber-600">{{ $expiringLeaseCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Completed Titles</div>
            <div class="text-xl font-semibold text-green-600">{{ $completedTitlesCount }}</div>
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
            <input type="text" wire:model.live.debounce.300ms="districtFilter" class="input input-bordered input-sm" placeholder="District…" />
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Delayed Applications</h3>
        <table class="table table-sm w-full">
            <tbody class="divide-y divide-gray-100">
                @forelse($delayedParcels as $parcel)
                    <tr>
                        <td class="px-2 py-2">{{ $parcel->property_name }}</td>
                        <td class="px-2 py-2 text-xs text-orange-600">Expected {{ $parcel->expected_completion_date?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-2 py-4 text-center text-gray-400">No delayed applications.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Expiring Leases</h3>
        <table class="table table-sm w-full">
            <tbody class="divide-y divide-gray-100">
                @forelse($expiringLeases as $parcel)
                    <tr>
                        <td class="px-2 py-2">{{ $parcel->property_name }}</td>
                        <td class="px-2 py-2 text-xs text-amber-600">Expires {{ $parcel->lease_expiry_date?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-2 py-4 text-center text-gray-400">No leases expiring soon.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
