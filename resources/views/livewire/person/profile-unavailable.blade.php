<div class="min-h-screen bg-gray-50">
    <x-slot name="header">
        <div class="min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-widest font-medium">People</div>
            <h1 class="text-base font-semibold text-gray-800 truncate">My Profile</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 py-16">
        <div class="bg-white border border-gray-200 rounded-xl p-8 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>

            <h2 class="text-lg font-semibold text-gray-800 mb-2">No profile linked to this account</h2>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                Your sign-in account isn't attached to a person record yet, so there is no profile to
                show. Administrator and service accounts often have none. An administrator can link
                one from Person Management.
            </p>

            <div class="flex items-center justify-center gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost">Back to dashboard</a>
                @can('view-persons')
                    <a href="{{ route('persons.all') }}" class="btn btn-sm border-0 text-white"
                       style="background:#982B55;">
                        Go to Person Management
                    </a>
                @endcan
            </div>
        </div>
    </div>
</div>
