<div class="min-h-full py-6 px-4 md:px-8" x-data="{ showDrawer: false, selectedMessage: null }">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Communication</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">Message History</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="fixed top-4 right-4 z-50">
            <div class="toast toast-top toast-end">
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
            class="fixed top-4 right-4 z-50">
            <div class="toast toast-top toast-end">
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        </div>
    @endif
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-2 bg-rose-100 rounded-lg">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Total Messages</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Success Rate</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['success_rate'] ?? 0 }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['by_status']['pending'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Failed</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['by_status']['failed'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Communication History</h3>
            <p class="text-sm text-gray-600 mt-1">View and filter your communication messages</p>
        </div>

        <div class="p-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="form-control">
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="input input-bordered input-sm"
                           placeholder="Search messages...">
                </div>

                <div class="form-control">
                    <select wire:model.live="channel_filter" class="select select-bordered select-sm">
                        <option value="">All Channels</option>
                        @foreach($available_channels as $channel)
                            <option value="{{ $channel }}">{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <select wire:model.live="status_filter" class="select select-bordered select-sm">
                        <option value="">All Statuses</option>
                        @foreach($available_statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <input type="date" wire:model.live="date_from"
                           class="input input-bordered input-sm"
                           placeholder="From Date">
                </div>

                <div class="form-control">
                    <input type="date" wire:model.live="date_to"
                           class="input input-bordered input-sm"
                           placeholder="To Date">
                </div>

                <div class="form-control">
                    <button wire:click="clearFilters" class="btn btn-ghost btn-sm">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left">Channel</th>
                        <th class="text-left">Recipient</th>
                        <th class="text-left">Subject/Content</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Sent By</th>
                        <th class="text-left">Created</th>
                        <th class="text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr class="hover:bg-gray-50">
                            <td>
                                <div class="flex items-center">
                                    <span class="text-lg mr-2">{{ $message->channel_icon }}</span>
                                    <span class="text-sm font-medium capitalize">{{ $message->channel }}</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    @if($message->recipientPerson)
                                        <div class="font-medium">{{ $message->recipientPerson->full_name }}</div>
                                        <div class="text-sm text-gray-600">{{ $message->recipient_identifier }}</div>
                                    @else
                                        <div class="font-medium">{{ $message->recipient_identifier }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    @if($message->subject)
                                        <div class="font-medium text-sm">{{ Str::limit($message->subject, 40) }}</div>
                                    @endif
                                    <div class="text-sm text-gray-600">{{ Str::limit($message->content, 60) }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $message->status_color }} badge-sm">
                                    {{ ucfirst($message->status) }}
                                </span>
                                @if($message->is_bulk_message)
                                    <span class="badge badge-info badge-sm ml-1">Bulk</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm">
                                    {{ $message->sentByUser?->name ?? 'System' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    <div>{{ $message->created_at->format('M d, Y') }}</div>
                                    <div class="text-gray-500">{{ $message->created_at->format('H:i A') }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <label for="drawer-message-{{ $message->id }}" class="btn btn-ghost btn-xs drawer-button">
                                        View
                                    </label>
                                    @if($message->bulk_message_id)
                                        <button class="btn btn-ghost btn-xs text-rose-600"
                                                onclick="viewBulkStats{{ $message->id }}.showModal()">
                                            Bulk Stats
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                No messages found.
                                @if($search || $channel_filter || $status_filter || $date_from || $date_to)
                                    <button wire:click="clearFilters" class="text-rose-600 hover:underline ml-1">Clear filters</button> to see all messages.
                                @else
                                    Start by sending your first message!
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($messages->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
    <!-- DaisyUI Drawer for message details -->
    </div>{{-- end max-w-6xl --}}

    @foreach($messages as $message)
        <div class="drawer drawer-end z-50">
            <input id="drawer-message-{{ $message->id }}" type="checkbox" class="drawer-toggle" />
            <div class="drawer-side">
                <label for="drawer-message-{{ $message->id }}" aria-label="close sidebar" class="drawer-overlay"></label>
                <div class="menu p-6 w-96 min-h-full bg-base-100 text-base-content relative">
                    <label class="absolute top-4 right-4 btn btn-sm btn-ghost" for="drawer-message-{{ $message->id }}" aria-label="Close message details">&times;</label>
                    <h2 class="text-xl font-bold mb-2">Message Details</h2>
                    <div class="mb-2"><span class="font-semibold">Channel:</span> {{ $message->channel }}</div>
                    <div class="mb-2"><span class="font-semibold">Recipient:</span> {{ $message->recipient_identifier }}</div>
                    <div class="mb-2"><span class="font-semibold">Subject:</span> {{ $message->subject }}</div>
                    <div class="mb-2"><span class="font-semibold">Content:</span> {{ $message->content }}</div>
                    <div class="mb-2"><span class="font-semibold">Status:</span> {{ $message->status }}</div>
                    <div class="mb-2"><span class="font-semibold">Sent By:</span> {{ $message->sentByUser?->name ?? 'System' }}</div>
                    <div class="mb-2"><span class="font-semibold">Created At:</span> {{ $message->created_at->format('M d, Y H:i A') }}</div>
                    <div class="mb-2"><span class="font-semibold">Bulk Message ID:</span> {{ $message->bulk_message_id }}</div>
                    <div class="divider"></div>
                    <div class="flex gap-2 mt-2">
                        <button class="btn btn-outline btn-sm" wire:click="resendMessage({{ $message->id }})">Resend</button>
                        <button class="btn btn-outline btn-sm" wire:click="editMessage({{ $message->id }})">Edit</button>
                        <button class="btn btn-outline btn-sm btn-error" wire:click="deleteMessage({{ $message->id }})">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
