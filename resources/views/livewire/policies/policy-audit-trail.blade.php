<div class="min-h-full">

    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Policies</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">Audit Trail — {{ $policy->title }}</h1>
        </div>
    </div>

<div class="py-6 px-4 md:px-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="table table-sm w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">When</th>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Actor</th>
                    <th class="px-4 py-3 text-left">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-mono">{{ $log->event }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $log->actor?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ json_encode($log->properties) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
</div>
