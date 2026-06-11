@php $user = auth()->user(); @endphp

{{-- Current org/role pill --}}
<div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-600 max-w-xs truncate">
    <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
    </svg>
    <span class="truncate font-medium">{{ user_current_organization_name() ?: 'No Organization' }}</span>
</div>

{{-- User dropdown --}}
<div class="dropdown dropdown-end">
    <label tabindex="0" class="flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
             style="background: #982B55;">
            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
        </div>
        <div class="hidden md:block text-left">
            <div class="text-sm font-medium text-gray-800 leading-none">{{ $user?->name ?? 'Guest' }}</div>
            <div class="text-xs text-gray-400 leading-none mt-0.5">
                {{ $user?->roles?->first()?->name ?? 'User' }}
            </div>
        </div>
        <svg class="w-3.5 h-3.5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </label>

    <ul tabindex="0"
        class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-xl w-48 border border-base-200 mt-1 z-50">
        @if($user)
            <li class="px-3 py-2 border-b border-base-200 mb-1">
                <div class="text-xs font-medium text-gray-800 truncate">{{ $user->name }}</div>
                <div class="text-xs text-gray-400">{{ $user->roles?->first()?->name ?? 'User' }}</div>
            </li>
            <li>
                <a href="{{ route('profile.show') }}" class="text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile Settings
                </a>
            </li>
            <li>
                <a href="#" class="text-sm flex items-center gap-2 text-red-500"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </li>
        @endif
    </ul>
</div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
