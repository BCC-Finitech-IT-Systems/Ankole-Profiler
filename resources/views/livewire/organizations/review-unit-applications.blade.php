<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Organizations Mgt</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Unit Membership Applications</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Bulk actions bar --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-sm btn-success"
                        wire:click="bulkApprove"
                        @if(empty($selectedIds)) disabled @endif>
                    Bulk Approve
                </button>
                <button type="button" class="btn btn-sm btn-error"
                        wire:click="bulkReject"
                        @if(empty($selectedIds)) disabled @endif>
                    Bulk Reject
                </button>
                @if(!empty($selectedIds))
                    <span class="text-sm text-gray-500">{{ count($selectedIds) }} selected</span>
                @endif
            </div>
        </div>

        {{-- Applications table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            @php
                $hasApplications = isset($applications) && count($applications);
            @endphp

            @if($hasApplications)
                <form wire:submit.prevent>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="w-10">
                                        <input type="checkbox" wire:model.live="selectAll"
                                               class="checkbox checkbox-sm"
                                               title="Select all pending applications">
                                    </th>
                                    <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Applicant</th>
                                    <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($applications as $app)
                                    <tr class="hover:bg-gray-50">
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedIds"
                                                   value="{{ $app->id }}" class="checkbox checkbox-sm">
                                        </td>
                                        <td class="font-medium text-gray-900">{{ $app->person?->full_name ?? 'N/A' }}</td>
                                        <td class="text-gray-700">{{ $app->unit?->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($app->status === 'pending')
                                                <span class="badge badge-warning badge-sm">Pending</span>
                                            @elseif($app->status === 'approved')
                                                <span class="badge badge-success badge-sm">Approved</span>
                                            @elseif($app->status === 'rejected')
                                                <span class="badge badge-error badge-sm">Rejected</span>
                                            @else
                                                <span class="badge badge-sm">{{ ucfirst($app->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex gap-1">
                                                <button class="btn btn-success btn-xs"
                                                        wire:click="approve({{ $app->id }})">Approve</button>
                                                <button class="btn btn-error btn-xs"
                                                        wire:click="reject({{ $app->id }})">Reject</button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                    <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No pending applications</p>
                    <p class="text-sm text-gray-400 mt-1">All membership applications will appear here for review.</p>
                </div>
            @endif
        </div>

        {{-- Selected application details --}}
        @if($selectedApplication)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Application Details</h3>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                    <div class="text-gray-500">Applicant</div>
                    <div class="font-medium">{{ $selectedApplication->person?->full_name ?? 'N/A' }}</div>
                    <div class="text-gray-500">Unit</div>
                    <div>{{ $selectedApplication->unit?->name ?? 'N/A' }}</div>
                    <div class="text-gray-500">Status</div>
                    <div>{{ ucfirst($selectedApplication->status) }}</div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-success btn-sm"
                            wire:click="approve({{ $selectedApplication->id }})">Approve</button>
                    <button class="btn btn-error btn-sm"
                            wire:click="reject({{ $selectedApplication->id }})">Reject</button>
                </div>
            </div>
        @endif

    </div>
</div>
