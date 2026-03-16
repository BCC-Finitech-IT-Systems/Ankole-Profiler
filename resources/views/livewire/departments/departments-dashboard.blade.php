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

            {{-- <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Total Projects</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_projects']) }}</p>
                </div>
            </div> --}}

            <div class="card bg-base-100 border border-primary/30 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Active Projects</p>
                    <p class="text-2xl font-bold text-success">{{ number_format($selectedDepartmentStats['active_projects'] ?? 0) }}</p>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/70">Organizations in Scope</p>
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

                    @if($hasSubCategories)
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
                    @endif
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

                <div class="mt-4" style="position: relative; height: 350px;"
                     wire:key="chart-{{ $chartPeriod }}-{{ $activeDepartmentId }}"
                     x-data="registrationChart()"
                     x-init="render(@js($registrationChartData), '{{ ucfirst($chartPeriod) }}')">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    @endif

    <div class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2 class="text-lg font-semibold">Department Projects</h2>

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
                                <th>Project Departments</th>
                                <th>Code</th>
                                <th>Admin</th>
                                <th>Persons</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-8 text-base-content/70">No projects found for this department.</td>
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

@if(!empty($registrationChartData['datasets']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    function registrationChart() {
        return {
            chart: null,
            render(chartData, periodLabel) {
                this.$nextTick(() => {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;

                    if (this.chart) {
                        this.chart.destroy();
                    }

                    this.chart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
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
                                    labels: { usePointStyle: true, padding: 16 }
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
