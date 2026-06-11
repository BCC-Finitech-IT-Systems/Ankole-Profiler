<div class="p-6 space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Departments Dashboard</h1>
            @if($isOrgAdmin)
                <p class="text-base-content/70">Your department performance and project activity overview.</p>
            @else
                <p class="text-base-content/70">Performance and activity overview by department.</p>
            @endif
        </div>

        <div class="form-control w-full max-w-xs">
            <label class="label">
                <span class="label-text">As of date</span>
            </label>
            <input type="date" wire:model.live="asOfDate" class="input input-bordered" />
        </div>
    </div>

    {{-- Summary Cards --}}
    @if($isOrgAdmin)
        {{-- Org Admin sees project-focused summary for their departments --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">My Departments</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_departments']) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-primary/30 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Active Projects</p>
                    <p class="text-2xl font-bold text-success">{{ number_format($selectedDepartmentStats['active_projects'] ?? 0) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Projects in Scope</p>
                    <p class="text-2xl font-bold">{{ $departmentOrganizations->count() }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-info/30 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Total Persons</p>
                    <p class="text-2xl font-bold text-info">{{ number_format($selectedDepartmentStats['total_persons'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Total Departments</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_departments']) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Active Departments</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['active_departments']) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Department Admins Assigned</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['departments_with_admin']) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Projects Across Departments</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_projects']) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-info/30 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Total Persons</p>
                    <p class="text-2xl font-bold text-info">{{ number_format($selectedDepartmentStats['total_persons'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="card xl:col-span-2 bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-lg font-semibold">{{ $isOrgAdmin ? 'My Department Focus' : 'Department Focus' }}</h2>
                    <div class="w-full md:w-auto grid grid-cols-1 {{ $isSuperAdmin ? 'md:grid-cols-2' : '' }} gap-2">
                        @if($isOrgAdmin)
                            {{-- Org Admin: show only their affiliated departments --}}
                            <select wire:model.live="activeDepartmentId" class="select select-bordered w-full md:min-w-80">
                                @forelse($departments as $departmentTab)
                                    <option value="{{ $departmentTab->id }}">
                                        {{ $departmentTab->name }} ({{ $departmentTab->projects_count }} projects)
                                    </option>
                                @empty
                                    <option value="">No departments available</option>
                                @endforelse
                            </select>
                        @else
                            <select wire:model.live="activeDepartmentId" class="select select-bordered w-full md:min-w-80">
                                @forelse($ankoleDepartments as $departmentTab)
                                    <option value="{{ $departmentTab->id }}">
                                        {{ $departmentTab->name }} ({{ $departmentTab->projects_count }} projects)
                                    </option>
                                @empty
                                    <option value="">No Ankole departments available</option>
                                @endforelse
                            </select>

                            @if($isSuperAdmin)
                                <select wire:model.live="activeDepartmentId" class="select select-bordered w-full md:min-w-80">
                                    <option value="">Departments outside Ankole Diocese</option>
                                    @forelse($nonAnkoleDepartments as $departmentTab)
                                        <option value="{{ $departmentTab->id }}">
                                            {{ $departmentTab->name }} — {{ $departmentTab->organization?->legal_name ?? 'No organization' }}
                                        </option>
                                    @empty
                                        <option value="">No non-Ankole departments available</option>
                                    @endforelse
                                </select>
                            @endif
                        @endif
                    </div>
                </div>

                @if($selectedDepartment)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <div class="space-y-3">
                            <div>
                                <p class="text-base font-semibold">{{ $selectedDepartment->name }}</p>
                                @if($hasSubCategories)
                                    <p class="text-sm text-base-content/70">Scope: organizations in categories {{ $selectedDepartment->subCategories->pluck('name')->join(', ') }}.</p>
                                @else
                                    <p class="text-sm text-base-content/70">{{ $selectedDepartment->organization?->legal_name ?? 'No organization assigned' }}</p>
                                @endif
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-box border border-base-300 p-3">
                                    <p class="text-xs text-base-content/70">Total Projects</p>
                                    <p class="text-xl font-semibold">{{ $selectedDepartmentStats['total_projects'] }}</p>
                                </div>
                                <div class="rounded-box border border-base-300 p-3">
                                    <p class="text-xs text-base-content/70">Project Units</p>
                                    <p class="text-xl font-semibold">{{ $selectedDepartmentStats['project_departments_count'] }}</p>
                                </div>
                                <div class="rounded-box border border-info/30 p-3">
                                    <p class="text-xs text-base-content/70">Total Persons</p>
                                    <p class="text-xl font-semibold text-info">{{ number_format($selectedDepartmentStats['total_persons'] ?? 0) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-base-content/70">Completion Rate</span>
                                    <span class="font-medium">{{ $selectedDepartmentStats['completion_rate'] }}%</span>
                                </div>
                                <progress class="progress progress-primary w-full" value="{{ $selectedDepartmentStats['completion_rate'] }}" max="100"></progress>
                            </div>

                            <div>
                                <p class="text-xs text-base-content/70 mb-1">Sub-categories</p>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($selectedDepartment->subCategories as $subCategory)
                                        <span class="badge {{ $subCategory->is_active ? 'badge-outline' : 'badge-ghost' }}">
                                            {{ $subCategory->name }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-base-content/60">No sub-categories configured.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- @if($hasSubCategories)
                        <div class="mt-4 rounded-box border border-base-300 p-3">
                            <p class="text-sm font-medium mb-2">Organizations in {{ $selectedDepartment->name }} scope ({{ $selectedDepartment->subCategories->pluck('name')->join('/') }})</p>
                            <div class="flex flex-wrap gap-2">
                                @forelse($departmentOrganizations as $organization)
                                    <button type="button" class="badge badge-outline cursor-pointer" wire:click="showOrganizationPersons({{ $organization->id }})">
                                        {{ $organization->display_name ?: $organization->legal_name }} ({{ ucfirst(strtolower($organization->category)) }})
                                    </button>
                                @empty
                                    <span class="text-sm text-base-content/70">No organizations found matching {{ $selectedDepartment->subCategories->pluck('name')->join('/') }} categories.</span>
                                @endforelse
                            </div>
                        </div>
                    @endif --}}
                @endif
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h2 class="text-lg font-semibold">Project Health</h2>

                @if($selectedDepartment)
                    <div class="space-y-3 mt-1">
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2">
                            <span class="text-sm text-base-content/70">Active</span>
                            <span class="font-semibold">{{ $selectedDepartmentStats['active_projects'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2">
                            <span class="text-sm text-base-content/70">Ongoing</span>
                            <span class="font-semibold">{{ $selectedDepartmentStats['ongoing_projects'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2">
                            <span class="text-sm text-base-content/70">Upcoming</span>
                            <span class="font-semibold">{{ $selectedDepartmentStats['upcoming_projects'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2">
                            <span class="text-sm text-base-content/70">Completed</span>
                            <span class="font-semibold">{{ $selectedDepartmentStats['completed_projects'] }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-base-content/70">No department selected.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-base">Status Breakdown</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Inactive</span>
                            <span>{{ $selectedDepartmentStats['inactive_projects'] ?? 0 }}</span>
                        </div>
                        <progress class="progress progress-neutral w-full" value="{{ $selectedDepartmentStats['inactive_projects'] ?? 0 }}" max="{{ max($selectedDepartmentStats['total_projects'] ?? 1, 1) }}"></progress>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Ongoing</span>
                            <span>{{ $selectedDepartmentStats['ongoing_projects'] ?? 0 }}</span>
                        </div>
                        <progress class="progress progress-info w-full" value="{{ $selectedDepartmentStats['ongoing_projects'] ?? 0 }}" max="{{ max($selectedDepartmentStats['total_projects'] ?? 1, 1) }}"></progress>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Completed</span>
                            <span>{{ $selectedDepartmentStats['completed_projects'] ?? 0 }}</span>
                        </div>
                        <progress class="progress progress-success w-full" value="{{ $selectedDepartmentStats['completed_projects'] ?? 0 }}" max="{{ max($selectedDepartmentStats['total_projects'] ?? 1, 1) }}"></progress>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-base">Sub-category Coverage</h3>
                <div class="space-y-2">
                    <div class="rounded-box border border-base-300 p-3">
                        <p class="text-xs text-base-content/70">Configured in Department</p>
                        <p class="text-xl font-semibold">{{ $selectedDepartment?->subCategories?->count() ?? 0 }}</p>
                    </div>
                    <div class="rounded-box border border-base-300 p-3">
                        <p class="text-xs text-base-content/70">Used by Projects</p>
                        <p class="text-xl font-semibold">{{ $selectedDepartmentStats['sub_category_coverage'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-base">Recent Projects</h3>
                <div class="space-y-2">
                    @forelse(($selectedDepartmentStats['recent_projects'] ?? collect()) as $recentProject)
                        <div class="rounded-box border border-base-300 p-2">
                            <p class="text-sm font-medium">{{ $recentProject->name }}</p>
                            <p class="text-xs text-base-content/70">
                                {{ $recentProject->departmentSubCategory?->name ?? $recentProject->sub_category ?? 'No sub-category' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-base-content/70">No recent projects for the selected department.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Persons Registered Per Project Chart --}}
    @if(!empty($registrationChartData['datasets']))
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Persons Registered Per Project</h2>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        {{-- Chart View Mode Selector --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-base-content/70">View:</span>
                            <select wire:model.live="chartViewMode" class="select select-bordered select-sm">
                                <option value="all">All Projects Combined</option>
                                <option value="single">Single Project</option>
                            </select>
                        </div>

                        {{-- Project Selector (visible only when single mode) --}}
                        @if($chartViewMode === 'single')
                            <select wire:model.live="selectedChartProjectId" class="select select-bordered select-sm min-w-48">
                                <option value="">Select a project...</option>
                                @foreach($chartableProjects as $project)
                                    <option value="{{ $project->id }}">
                                        {{ $project->name }}
                                        @if($project->department?->organization)
                                            ({{ $project->department->organization->display_name ?: $project->department->organization->legal_name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Period Selector --}}
                        <div class="btn-group">
                            <button wire:click="setChartPeriod('weekly')"
                                class="btn btn-sm {{ $chartPeriod === 'weekly' ? 'btn-primary' : 'btn-outline' }}">
                                Weekly
                            </button>
                            <button wire:click="setChartPeriod('monthly')"
                                class="btn btn-sm {{ $chartPeriod === 'monthly' ? 'btn-primary' : 'btn-outline' }}">
                                Monthly
                            </button>
                            <button wire:click="setChartPeriod('yearly')"
                                class="btn btn-sm {{ $chartPeriod === 'yearly' ? 'btn-primary' : 'btn-outline' }}">
                                Yearly
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Selected Project Info Badge --}}
                @if($chartViewMode === 'single' && $selectedChartProject)
                    <div class="flex items-center gap-2 mt-2">
                        <span class="badge badge-primary badge-lg gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ $selectedChartProject->name }}
                        </span>
                        @if($selectedChartProject->department?->organization)
                            <span class="badge badge-outline">
                                {{ $selectedChartProject->department->organization->display_name ?: $selectedChartProject->department->organization->legal_name }}
                            </span>
                        @endif
                        <span class="text-sm text-base-content/70">
                            Total: {{ number_format($selectedChartProject->persons_count ?? 0) }} persons
                        </span>
                    </div>
                @endif

                <div class="mt-4" style="position: relative; height: 350px;"
                     wire:key="chart-{{ $chartPeriod }}-{{ $activeDepartmentId }}-{{ $chartViewMode }}-{{ $selectedChartProjectId }}"
                     x-data="registrationChart()"
                     x-init="render(@js($registrationChartData), '{{ ucfirst($chartPeriod) }}', '{{ $chartViewMode }}')">
                    <canvas x-ref="canvas"></canvas>
                </div>

                {{-- Quick Project Switcher (clickable chips for single mode) --}}
                @if($chartViewMode === 'single' && $chartableProjects->count() > 1)
                    <div class="mt-4 pt-4 border-t border-base-300">
                        <p class="text-xs text-base-content/70 mb-2">Quick switch:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($chartableProjects->take(10) as $project)
                                <button
                                    wire:click="$set('selectedChartProjectId', {{ $project->id }})"
                                    class="badge cursor-pointer transition-all hover:scale-105 {{ $selectedChartProjectId == $project->id ? 'badge-primary' : 'badge-outline hover:badge-primary/50' }}"
                                >
                                    {{ Str::limit($project->name, 20) }}
                                </button>
                            @endforeach
                            @if($chartableProjects->count() > 10)
                                <span class="badge badge-ghost">+{{ $chartableProjects->count() - 10 }} more</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Department Projects</h2>
                @can('create-projects')
                    <button wire:click="openCreateProject"
                            class="btn btn-sm border-0 text-white gap-1" style="background:#982B55;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        New Project
                    </button>
                @endcan
            </div>

            @if($selectedDepartment)
                <div class="overflow-x-auto mt-2">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th>Organization</th>
                                <th>Org Category</th>
                                <th>Sub-Category</th>
                                <th>Unit</th>
                                <th>Client</th>
                                <th>Project Departments</th>
                                <th>Code</th>
                                <th>Admin</th>
                                <th>Persons</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($selectedDepartmentProjects as $index => $project)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('projects.persons', ['project' => $project->id]) }}" class="link link-primary">
                                            {{ $project->name }}
                                        </a>
                                    </td>
                                    <td>{{ $project->department?->organization?->display_name ?: ($project->department?->organization?->legal_name ?? '—') }}</td>
                                    <td>{{ $project->department?->organization?->category ? ucfirst(strtolower(trim($project->department->organization->category))) : '—' }}</td>
                                    <td>{{ $project->departmentSubCategory?->name ?? $project->sub_category ?? '—' }}</td>
                                    <td>
                                        @if($project->organizationUnit)
                                            <span class="badge badge-outline badge-sm">{{ $project->organizationUnit->name }}</span>
                                        @else
                                            <span class="text-base-content/40">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->client)
                                            <span class="text-sm font-medium">{{ $project->client->full_name }}</span>
                                        @elseif($project->external_client_name)
                                            <span class="text-sm text-base-content/70 italic">{{ $project->external_client_name }}</span>
                                        @else
                                            <span class="text-base-content/40">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($project->projectDepartments->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($project->projectDepartments as $projectDepartment)
                                                    <span class="badge {{ $projectDepartment->is_active ? 'badge-outline' : 'badge-ghost' }}">
                                                        {{ $projectDepartment->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $project->code ?: '—' }}</td>
                                    <td>{{ $project->admin?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-info badge-outline">{{ number_format($project->persons_count) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $project->is_active ? 'badge-success' : 'badge-ghost' }}">
                                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $project->starts_on ? $project->starts_on->format('Y-m-d') : '—' }}</td>
                                    <td>{{ $project->ends_on ? $project->ends_on->format('Y-m-d') : '—' }}</td>
                                    <td>
                                        <div class="flex items-center gap-1">
                                            <button
                                                wire:click="viewProjectChart({{ $project->id }})"
                                                class="btn btn-xs btn-ghost btn-circle tooltip"
                                                data-tip="View chart"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                            </button>
                                            @can('edit-projects')
                                                <button
                                                    wire:click="openEditProject({{ $project->id }})"
                                                    class="btn btn-xs btn-ghost btn-circle tooltip"
                                                    data-tip="Edit project"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center py-8 text-base-content/70">No projects found for this department.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($hasSubCategories)
                    <div class="mt-4 rounded-box border border-base-300 p-3">
                        <p class="text-sm font-medium mb-2">{{ $selectedDepartment->subCategories->pluck('name')->join('/') }} Organizations</p>
                        <div class="overflow-x-auto">
                            <table class="table table-sm w-full">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Organization</th>
                                        <th>Category</th>
                                        <th>Persons</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($departmentOrganizations as $index => $organization)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $organization->display_name ?: $organization->legal_name }}</td>
                                            <td>{{ ucfirst(strtolower(trim($organization->category))) }}</td>
                                            <td>
                                                <span class="badge badge-info badge-outline">{{ number_format($organization->persons_count) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-base-content/70">No organizations found matching {{ $selectedDepartment->subCategories->pluck('name')->join('/') }} categories.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="font-semibold bg-base-200/50">
                                        <td colspan="3" class="text-right">Total Persons in {{ $selectedDepartment->name }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ number_format($selectedDepartmentStats['total_persons'] ?? 0) }}</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            @else
                <p class="text-sm text-base-content/70">No department selected.</p>
            @endif
        </div>
    </div>
</div>

{{-- Project Create/Edit Modal --}}
@if($showProjectModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeProjectModal">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-bold">{{ $editingProjectId ? 'Edit Project' : 'New Project' }}</h3>
            <button wire:click="closeProjectModal" class="btn btn-sm btn-ghost btn-circle">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            {{-- Name --}}
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Project Name <span class="text-red-500">*</span></span></label>
                <input type="text" wire:model="projectName" class="input input-bordered w-full" placeholder="Enter project name" />
                @error('projectName') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Code --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Code</span></label>
                    <input type="text" wire:model="projectCode" class="input input-bordered w-full" placeholder="e.g. PROJ-001" />
                </div>

                {{-- Status --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Status</span></label>
                    <select wire:model="projectIsActive" class="select select-bordered w-full">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            {{-- Department --}}
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Department <span class="text-red-500">*</span></span></label>
                <select wire:model.live="projectDepartmentId" class="select select-bordered w-full">
                    <option value="">Select department…</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('projectDepartmentId') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Organization Unit --}}
            @if(count($availableUnits) > 0)
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Organization Unit</span></label>
                    <select wire:model="projectOrganizationUnitId" class="select select-bordered w-full">
                        <option value="">None</option>
                        @foreach($availableUnits as $unit)
                            <option value="{{ $unit['id'] }}">{{ $unit['name'] }}{{ $unit['code'] ? ' ('.$unit['code'].')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($projectDepartmentId)
                <p class="text-xs text-base-content/50 italic">No active units found for the selected department.</p>
            @endif

            {{-- Client (person search or external name) --}}
            <div class="rounded-lg border border-base-200 p-4 space-y-3">
                <p class="text-sm font-semibold text-base-content/70 uppercase tracking-wide">Client</p>

                {{-- Person search --}}
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs">Internal client (person)</span></label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="projectClientSearch"
                               placeholder="Search by name or person ID…"
                               class="input input-bordered input-sm w-full pr-8" />
                        @if($projectClientPersonId)
                            <svg class="w-4 h-4 text-green-500 absolute right-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                        @if(count($projectClientResults))
                            <ul class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg text-sm max-h-48 overflow-y-auto">
                                @foreach($projectClientResults as $p)
                                    <li wire:click="selectProjectClient({{ $p['id'] }}, '{{ addslashes($p['given_name'] . ' ' . $p['family_name']) }}')"
                                        class="px-3 py-2 cursor-pointer hover:bg-rose-50 flex items-center justify-between">
                                        <span>{{ $p['given_name'] }} {{ $p['family_name'] }}</span>
                                        <span class="text-xs text-gray-400">{{ $p['person_id'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if($projectClientPersonId)
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-green-700 font-medium">{{ $projectClientSelectedName }}</span>
                            <button type="button" wire:click="clearProjectClient" class="text-xs text-red-400 hover:text-red-600">&times; Clear</button>
                        </div>
                    @endif
                </div>

                {{-- External client name (only if no internal person selected) --}}
                @if(!$projectClientPersonId)
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs">Or external client name</span></label>
                        <input type="text" wire:model="projectExternalClientName" class="input input-bordered input-sm w-full" placeholder="External organisation or individual name" />
                    </div>
                @endif
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Start Date</span></label>
                    <input type="date" wire:model="projectStartsOn" class="input input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">End Date</span></label>
                    <input type="date" wire:model="projectEndsOn" class="input input-bordered w-full" />
                    @error('projectEndsOn') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Description</span></label>
                <textarea wire:model="projectDescription" rows="3" class="textarea textarea-bordered w-full" placeholder="Optional project description…"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 p-6 border-t">
            <button wire:click="closeProjectModal" class="btn btn-ghost">Cancel</button>
            <button wire:click="saveProject"
                    wire:loading.attr="disabled"
                    class="btn border-0 text-white" style="background:#982B55;">
                <span wire:loading wire:target="saveProject">Saving…</span>
                <span wire:loading.remove wire:target="saveProject">{{ $editingProjectId ? 'Save Changes' : 'Create Project' }}</span>
            </button>
        </div>
    </div>
</div>
@endif

@if(!empty($registrationChartData['datasets']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    function registrationChart() {
        return {
            chart: null,
            render(chartData, periodLabel, viewMode) {
                this.$nextTick(() => {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;

                    if (this.chart) {
                        this.chart.destroy();
                    }

                    // Determine chart type based on view mode
                    const chartType = viewMode === 'single' ? 'bar' : 'line';
                    const isSingleMode = viewMode === 'single';

                    this.chart = new Chart(canvas, {
                        type: chartType,
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets.map((dataset, index) => ({
                                ...dataset,
                                // For single project view, use bar styling
                                ...(isSingleMode ? {
                                    backgroundColor: dataset.borderColor || 'rgba(99, 102, 241, 0.7)',
                                    borderColor: dataset.borderColor || 'rgba(99, 102, 241, 1)',
                                    borderWidth: 2,
                                    borderRadius: 4,
                                    barPercentage: 0.7,
                                    categoryPercentage: 0.8
                                } : {})
                            }))
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { usePointStyle: true, padding: 16 },
                                    display: !isSingleMode || chartData.datasets.length > 1
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' persons';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0 },
                                    title: { display: true, text: 'Persons Registered' }
                                },
                                x: {
                                    title: { display: true, text: periodLabel + ' Period' }
                                }
                            }
                        }
                    });
                });
            }
        };
    }
</script>
@endif
