<div class="p-8" style="background-color: #eeeff2;">
    @role(['Person', 'Organization Admin'])
    {{-- Person Dashboard: Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8 max-w-7xl mx-auto">
        <!-- Profile Card -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-accent/20 to-accent/10 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">My Profile</h3>
                <p class="text-base-content/70 text-xs mb-3">View and update your personal information.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-accent btn-sm">Edit Profile</a>
            </div>
        </div>
        <!-- Affiliations Card -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-info/20 to-info/10 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">My Affiliations</h3>
                <p class="text-base-content/70 text-xs mb-3">See your Projects, groups, and roles.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-info btn-sm">View Affiliations</a>
            </div>
        </div>
        <!-- Documents Card -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/10 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">My Documents</h3>
                <p class="text-base-content/70 text-xs mb-3">Upload and manage your documents.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-secondary btn-sm">Manage Documents</a>
            </div>
        </div>
        <!-- Notifications Card -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-warning/20 to-warning/10 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM5 12V7a7 7 0 1114 0v5l4 3v5a1 1 0 01-1 1H6a1 1 0 01-1-1v-5l4-3z" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">My Notifications</h3>
                <p class="text-base-content/70 text-xs mb-3">View your latest messages and alerts.</p>
                <a href="{{ route('person.notifications') }}" class="btn btn-outline btn-warning btn-sm">View Notifications</a>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Activity -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-header bg-base-200 p-4 border-b border-base-300">
                <h3 class="card-title text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Recent Activity
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="divide-y divide-base-300">
                    @forelse($recentActivities as $activity)
                        <div class="p-4 hover:bg-base-50 transition-all duration-200 cursor-pointer flex items-start gap-3">
                            <div class="w-8 h-8 bg-{{ $activity['badge_color'] }}/10 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4 text-{{ $activity['badge_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-base-content">{{ $activity['title'] }}</p>
                                <p class="text-xs text-base-content/70 mt-1">{{ $activity['description'] }}</p>
                                <p class="text-xs text-base-content/50 mt-1">{{ $activity['time'] }}</p>
                            </div>
                            <div class="badge badge-{{ $activity['badge_color'] }} badge-sm">{{ $activity['badge'] }}</div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <p class="text-sm text-gray-500">No recent activity</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <!-- Support & Help -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-header bg-base-200 p-4 border-b border-base-300">
                <h3 class="card-title text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Support & Help
                </h3>
            </div>
            <div class="card-body">
                <p class="mb-4 text-base-content/70">Need assistance? Browse our help articles or contact support.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-outline btn-info btn-sm">Get Support</a>
            </div>
        </div>
    </div>
    @endrole

    @hasanyrole('Super Admin')
    {{-- Quick Actions Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8 max-w-7xl mx-auto">


        <!-- Organizations Card -->
        <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-200 border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 bg-gradient-to-br from-info/20 to-info/10 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">Projects</h3>
                <p class="text-base-content/70 text-xs mb-3">Manage Projects hierarchy and structures.</p>
                <a href="{{ route('organizations.index') }}" class="btn btn-outline btn-info btn-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                    </svg>
                    View Projects
                </a>
            </div>
        </div>

        <!-- Person Registry Card -->
        <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-200 border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 bg-gradient-to-br from-accent/20 to-accent/10 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">Person Registry</h3>
                <p class="text-base-content/70 text-xs mb-3">Manage person identities, profiles, and master records.</p>
                <a href="{{ route('persons.create') }}" class="btn btn-outline btn-accent btn-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Person
                </a>
            </div>
        </div>

        <!-- Affiliations Card -->
        <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-200 border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 bg-gradient-to-br from-secondary/20 to-secondary/10 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">Affiliations</h3>
                <p class="text-base-content/70 text-xs mb-3">Link persons to organizations with roles and positions.</p>
                <a href="#" class="btn btn-outline btn-secondary btn-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                    </svg>
                    Manage Links
                </a>
            </div>
        </div>

        <!-- Compliance Card -->
        <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-200 border border-base-300">
            <div class="card-body items-center text-center p-4">
                <div class="w-12 h-12 bg-gradient-to-br from-success/20 to-success/10 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="card-title text-base font-bold mb-1">Compliance</h3>
                <p class="text-base-content/70 text-xs mb-3">Data privacy, consent management, and audit trails.</p>
                <a href="#" class="btn btn-outline btn-success btn-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
                    </svg>
                    View Status
                </a>
            </div>
        </div>
    </div>

    {{-- System Statistics --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-base-content mb-2">System Overview</h2>
                <p class="text-base-content/70">Current status and key metrics for the person registry system</p>
            </div>
            <button wire:click="refreshData"
                    wire:loading.attr="disabled"
                    class="btn btn-accent btn-sm gap-2">
                <svg wire:loading.remove wire:target="refreshData" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="refreshData" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="refreshData">Refresh Data</span>
                <span wire:loading wire:target="refreshData">Refreshing...</span>
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Total Persons -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-accent mb-1">{{ number_format($stats['total_persons'] ?? 0) }}</div>
                    <div class="text-xs text-base-content/70">Total Persons</div>
                    @if(($stats['persons_today'] ?? 0) > 0)
                        <div class="badge badge-success badge-xs mt-1">+{{ $stats['persons_today'] }} today</div>
                    @endif
                </div>
            </div>

            <!-- Active Organizations -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-info mb-1">{{ number_format($stats['total_organizations'] ?? 0) }}</div>
                    <div class="text-xs text-base-content/70">Projects</div>
                    @if(($stats['new_organizations'] ?? 0) > 0)
                        <div class="badge badge-info badge-xs mt-1">{{ $stats['new_organizations'] }} new</div>
                    @endif
                </div>
            </div>

            <!-- Pending Verifications -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-warning mb-1">{{ number_format($stats['pending_verifications'] ?? 0) }}</div>
                    <div class="text-xs text-base-content/70">Pending Verifications</div>
                    @if(($stats['pending_verifications'] ?? 0) > 0)
                        <div class="badge badge-warning badge-xs mt-1">Action needed</div>
                    @endif
                </div>
            </div>

            <!-- Active Affiliations -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-secondary mb-1">{{ number_format($stats['active_affiliations'] ?? 0) }}</div>
                    <div class="text-xs text-base-content/70">Active Affiliations</div>
                    @if(($stats['expired_affiliations'] ?? 0) > 0)
                        <div class="badge badge-secondary badge-xs mt-1">{{ $stats['expired_affiliations'] }} expired</div>
                    @endif
                </div>
            </div>

            <!-- Pending Consents -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-error mb-1">{{ number_format($stats['pending_consents'] ?? 0) }}</div>
                    <div class="text-xs text-base-content/70">Pending Consents</div>
                    @if(($stats['pending_consents'] ?? 0) > 0)
                        <div class="badge badge-error badge-xs mt-1">Urgent</div>
                    @endif
                </div>
            </div>

            <!-- System Health -->
            <div class="card bg-base-100 shadow-md hover:shadow-lg transition-all duration-200 border border-base-300">
                <div class="card-body p-4 text-center">
                    <div class="text-2xl font-bold text-success mb-1">{{ number_format($stats['system_health'] ?? 0, 1) }}%</div>
                    <div class="text-xs text-base-content/70">System Health</div>
                    <div class="badge badge-{{ ($stats['system_health'] ?? 0) > 95 ? 'success' : (($stats['system_health'] ?? 0) > 75 ? 'warning' : 'error') }} badge-xs mt-1">
                        {{ ($stats['system_health'] ?? 0) > 95 ? 'Optimal' : (($stats['system_health'] ?? 0) > 75 ? 'Good' : 'Needs Attention') }}
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Recent Activities & Notifications --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Activity -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-header bg-base-200 p-4 border-b border-base-300">
                <div class="flex items-center justify-between">
                    <h3 class="card-title text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Recent Activity
                    </h3>
                    <button class="btn btn-ghost btn-sm">View All</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="divide-y divide-base-300">
                    @forelse($recentActivities as $activity)
                        <div class="p-4 hover:bg-base-50 transition-all duration-200 cursor-pointer flex items-start gap-3">
                            <div class="w-8 h-8 bg-{{ $activity['badge_color'] }}/10 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4 text-{{ $activity['badge_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($activity['icon'] === 'user-group')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    @elseif($activity['icon'] === 'building')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                                    @endif
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-base-content">{{ $activity['title'] }}</p>
                                <p class="text-xs text-base-content/70 mt-1">{{ $activity['description'] }}</p>
                                <p class="text-xs text-base-content/50 mt-1">{{ $activity['time'] }}</p>
                            </div>
                            <div class="badge badge-{{ $activity['badge_color'] }} badge-sm">{{ $activity['badge'] }}</div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <p class="text-sm text-gray-500">No recent activity</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Alerts & Notifications -->
        <div class="card bg-base-100 shadow-lg border border-base-300">
            <div class="card-header bg-base-200 p-4 border-b border-base-300">
                <div class="flex items-center justify-between">
                    <h3 class="card-title text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM5 12V7a7 7 0 1114 0v5l4 3v5a1 1 0 01-1 1H6a1 1 0 01-1-1v-5l4-3z" />
                        </svg>
                        Alerts & Notifications
                    </h3>
                    <button class="btn btn-ghost btn-sm">Mark All Read</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="divide-y divide-base-300">
                    @forelse($alerts as $alert)
                        <div class="p-4 bg-{{ $alert['level'] }}/5 border-l-4 border-{{ $alert['level'] }} flex items-start gap-3">
                            <div class="w-8 h-8 bg-{{ $alert['level'] }}/10 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4 text-{{ $alert['level'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($alert['icon'] === 'exclamation-circle')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    @elseif($alert['icon'] === 'exclamation-triangle')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    @elseif($alert['icon'] === 'information-circle')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    @endif
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-base-content">{{ $alert['title'] }}</p>
                                <p class="text-xs text-base-content/70 mt-1">{{ $alert['description'] }}</p>
                                <p class="text-xs text-base-content/50 mt-1">{{ $alert['priority'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-green-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-gray-500">No alerts - All systems operational</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endhasanyrole


    {{-- Loading Overlay --}}
    <div wire:loading wire:target="refreshData" class="fixed inset-0 flex items-center justify-center bg-transparent z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <p class="font-medium text-gray-900">Refreshing Dashboard...</p>
                <p class="text-sm text-gray-500">Fetching latest data from database</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Listen for dashboard refresh event
    window.addEventListener('dashboard-refreshed', event => {
        // Show success notification
        console.log('Dashboard refreshed:', event.detail.message);

        // You can add a toast notification here if you have one
        // showToast('success', event.detail.message);
    });
</script>
@endpush
