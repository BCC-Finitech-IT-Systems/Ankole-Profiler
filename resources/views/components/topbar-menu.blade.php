@php $user = auth()->user(); @endphp
{{-- <button class="btn btn-ghost btn-sm gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    Help
</button> --}}
{{-- <button class="btn btn-ghost btn-sm">System Status</button> --}}

<!-- Current Organization Display -->
    <div class="flex items-center gap-2 px-3 py-2 bg-base-200 rounded-lg">
        <svg class="w-4 h-4 text-base-content/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M7 7h10M7 11h10M7 15h10" />
        </svg>
        <span class="text-sm font-medium text-base-content/80">
            {{ user_current_organization_name() }} - {{ $user->roles->first()->name ? : 'No Role' }}
        </span>
    </div>

<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-info btn-sm gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        {{ $user ? $user->name : 'Guest' }}
        <span
            class="badge badge-xs">{{ $user && $user->roles->first()?->name ? : 'User' }}</span>
    </label>
    <ul tabindex="0"
        class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52 border border-base-300 mt-2">
        @if ($user)
            <li><a href="{{ route('profile.show') }}">Profile Settings</a></li>
            <li><a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
            </li>
        @endif
    </ul>
</div>
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
