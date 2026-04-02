<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Department Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($userDepartment)
                            {{ $userDepartment->name }} Department Overview
                        @else
                            Organization Admin Dashboard
                        @endif
                    </p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3">
                    <!-- Date Range Filter -->
                    <select wire:model.live="dateRange"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                    </select>

                    <!-- Organization Filter -->
                    @if(!empty($organizationsInScope))
                    <select wire:model.live="selectedOrganizationId"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Projects</option>
                        @foreach($organizationsInScope as $org)
                            <option value="{{ $org['id'] }}">{{ $org['legal_name'] ?? $org['display_name'] }}</option>
                        @endforeach
                    </select>
                    @endif

                    <button wire:click="refreshData"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg wire:loading wire:target="refreshData" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="refreshData" class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-8xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Persons -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Persons</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            {{ number_format($overviewStats['total_persons'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- New Registrations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">New Registrations</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            {{ number_format($overviewStats['new_persons'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">In selected period</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Organizations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Projects</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            {{ number_format($overviewStats['total_organizations'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">In your department</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Project Heads -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Project Heads</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">
                            {{ number_format($overviewStats['project_heads'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Appointed</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Monthly Registrations -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Registrations</h3>
                <div class="h-48">
                    @if(!empty($monthlyRegistrations))
                        <div class="flex items-end justify-between h-full space-x-2">
                            @php $maxCount = max(array_column($monthlyRegistrations, 'count')) ?: 1; @endphp
                            @foreach($monthlyRegistrations as $data)
                                <div class="flex flex-col items-center flex-1">
                                    <span class="text-xs text-gray-600 mb-1">{{ $data['count'] }}</span>
                                    <div class="w-full bg-indigo-500 rounded-t transition-all duration-300 hover:bg-indigo-600"
                                         style="height: {{ ($data['count'] / $maxCount) * 100 }}%; min-height: 4px;">
                                    </div>
                                    <span class="text-xs text-gray-500 mt-2">{{ $data['month'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full text-gray-500">
                            No data available
                        </div>
                    @endif
                </div>
            </div>

            <!-- Gender Distribution -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Gender Distribution</h3>
                <div class="flex items-center justify-center h-48">
                    @if(($genderDistribution['total'] ?? 0) > 0)
                        <div class="flex items-center space-x-8">
                            <div class="text-center">
                                <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <span class="text-2xl font-bold text-blue-600">{{ $genderDistribution['male_percentage'] }}%</span>
                                </div>
                                <p class="text-sm text-gray-600">Male</p>
                                <p class="text-xs text-gray-400">{{ number_format($genderDistribution['male']) }}</p>
                            </div>
                            <div class="text-center">
                                <div class="w-20 h-20 rounded-full bg-pink-100 flex items-center justify-center mb-2">
                                    <span class="text-2xl font-bold text-pink-600">{{ $genderDistribution['female_percentage'] }}%</span>
                                </div>
                                <p class="text-sm text-gray-600">Female</p>
                                <p class="text-xs text-gray-400">{{ number_format($genderDistribution['female']) }}</p>
                            </div>
                        </div>
                    @else
                        <div class="text-gray-500">No gender data available</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Organizations & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Organizations in Scope -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Projects in Your Department</h3>
                    <a href="{{ route('persons.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                        + Add Person
                    </a>
                </div>
                <div class="overflow-x-auto max-h-80">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Persons</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($organizationsInScope as $org)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $org['legal_name'] ?? $org['display_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $org['category'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ number_format($org['total_persons'] ?? 0) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                                        No projects found in your department
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Project Heads List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Project Heads</h3>
                </div>
                <div class="divide-y divide-gray-200 max-h-80 overflow-y-auto">
                    @forelse($projectHeads as $ph)
                        <div class="px-6 py-4 flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-indigo-600">
                                        {{ substr($ph['name'], 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">{{ $ph['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $ph['organization'] ?? 'No project assigned' }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500">
                            No Project Heads appointed yet
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Persons -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Recent Registrations</h3>
                <a href="{{ route('persons.all') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    View All →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentPersons as $person)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ substr($person['name'], 0, 2) }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $person['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $person['email'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $person['organization'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                    {{ $person['created_at'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No recent registrations
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
