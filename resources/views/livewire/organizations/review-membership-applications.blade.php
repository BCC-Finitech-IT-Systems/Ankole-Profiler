<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Membership</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Membership Applications</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        @if (session('success'))
            <div class="alert alert-success text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @if($applications->count())
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Diocese</th>
                                <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Applied</th>
                                <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($applications as $application)
                                <tr class="hover:bg-gray-50">
                                    <td class="text-sm text-gray-800">
                                        {{ $application->person?->full_name ?? trim(($application->person?->given_name ?? '') . ' ' . ($application->person?->family_name ?? '')) }}
                                    </td>
                                    <td class="text-sm text-gray-600">{{ $application->Organization?->display_name }}</td>
                                    <td class="text-sm text-gray-500">{{ $application->created_at?->diffForHumans() }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="btn btn-xs btn-success"
                                                    wire:click="approve({{ $application->id }})"
                                                    wire:confirm="Approve this membership application?">
                                                Approve
                                            </button>
                                            <button type="button" class="btn btn-xs btn-error"
                                                    wire:click="reject({{ $application->id }})"
                                                    wire:confirm="Reject this membership application?">
                                                Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $applications->links() }}
                </div>
            @else
                <div class="p-8 text-center text-sm text-gray-500">
                    No pending membership applications.
                </div>
            @endif
        </div>
    </div>
</div>
