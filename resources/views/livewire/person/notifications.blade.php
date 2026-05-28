<div class="min-h-full py-6 px-4 md:px-8">

    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">Person Registry</div>
                <h1 class="text-base font-semibold text-gray-800 truncate">My Notifications</h1>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-4">

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <span class="bg-rose-100 text-rose-600 rounded-lg p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-gray-700">Recent Activities</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @forelse($recentActivities as $activity)
                        <li class="flex items-start gap-2">
                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mt-1.5 shrink-0"></span>
                            <div>
                                <div class="font-medium text-gray-800">{{ $activity['title'] }}</div>
                                <div class="text-xs text-gray-500">{{ $activity['description'] }}</div>
                                <div class="text-xs text-gray-400">{{ $activity['time'] }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-gray-400 text-xs">No recent activities</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <span class="bg-purple-100 text-purple-600 rounded-lg p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-gray-700">Ongoing Events</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @forelse($ongoingEvents ?? [] as $event)
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-purple-400 rounded-full shrink-0"></span>
                            <span>{{ $event }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400 text-xs">No ongoing events</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <span class="bg-green-100 text-green-600 rounded-lg p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-gray-700">System Notifications</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @forelse($systemNotifications ?? [] as $sysnote)
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full shrink-0"></span>
                            <span>{{ $sysnote }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400 text-xs">No system notifications</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Notification list --}}
        @if($notifications->count())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
                @foreach($notifications as $i => $notification)
                    @if($i < 5)
                    <div class="p-4 flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="badge badge-sm {{ $notification->read_at ? 'badge-neutral' : 'badge-accent' }}">
                                {{ $notification->read_at ? 'Read' : 'Unread' }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="font-medium text-gray-800 text-sm">{{ $notification->data['title'] ?? 'Notification' }}</div>
                        <div class="text-gray-500 text-sm">{{ $notification->data['body'] ?? '' }}</div>
                        @if(isset($notification->data['action_url']))
                            <a href="{{ $notification->data['action_url'] }}"
                               class="mt-1 inline-flex btn btn-xs border-0 text-white w-fit" style="background:#982B55;">
                                View Details
                            </a>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>
            <div>{{ $notifications->links() }}</div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col items-center justify-center py-16 text-center px-4">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <p class="text-gray-500 font-medium">No notifications</p>
                <p class="text-sm text-gray-400 mt-1">You're all caught up.</p>
            </div>
        @endif

    </div>
</div>
