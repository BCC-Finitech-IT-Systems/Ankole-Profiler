<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Audit Report Register</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Audit Dashboard</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8 space-y-4">

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Total</div>
            <div class="text-xl font-semibold text-gray-800">{{ $total }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Draft</div>
            <div class="text-xl font-semibold text-gray-600">{{ $draft }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Issued</div>
            <div class="text-xl font-semibold text-blue-600">{{ $issued }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Under Review</div>
            <div class="text-xl font-semibold text-purple-600">{{ $underReview }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Closed</div>
            <div class="text-xl font-semibold text-green-600">{{ $closed }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Restricted</div>
            <div class="text-xl font-semibold text-orange-600">{{ $restrictedCount }}</div>
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
            <select wire:model.live="auditTypeFilter" class="select select-bordered select-sm">
                <option value="">All Types</option>
                <option value="internal">Internal</option>
                <option value="external">External</option>
                <option value="financial">Financial</option>
                <option value="compliance">Compliance</option>
                <option value="operational">Operational</option>
                <option value="institutional">Institutional</option>
            </select>
            <select wire:model.live="statusFilter" class="select select-bordered select-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="issued">Issued</option>
                <option value="under_review">Under Review</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">By Audit Type</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
            @forelse($byType as $type => $count)
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <span class="text-gray-600">{{ ucfirst($type) }}</span>
                    <span class="font-medium">{{ $count }}</span>
                </div>
            @empty
                <p class="text-gray-400">No audit reports yet.</p>
            @endforelse
        </div>
    </div>
</div>
</div>
