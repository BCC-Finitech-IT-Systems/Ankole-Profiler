<?php

namespace App\Livewire\Departments;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

use Illuminate\Support\Collection;
use App\Models\Project;
use Livewire\Attributes\Computed;

class DepartmentsDashboard extends Component
{
    public $activeDepartmentId = null;
    public $asOfDate = null;
    public $chartPeriod = 'monthly';
    public $selectedOrganizationPersons = [];

    // Chart view mode: 'all' for combined view, 'single' for individual project
    public string $chartViewMode = 'all';

    // Selected project ID when in single chart view mode
    public ?int $selectedChartProjectId = null;

    // Project create/edit modal
    public bool $showProjectModal = false;
    public ?int $editingProjectId = null;
    public string $projectName = '';
    public string $projectCode = '';
    public string $projectDescription = '';
    public ?int $projectDepartmentId = null;
    public ?int $projectOrganizationUnitId = null;
    public ?int $projectClientPersonId = null;
    public string $projectClientSearch = '';
    public string $projectExternalClientName = '';
    public bool $projectIsActive = true;
    public string $projectStartsOn = '';
    public string $projectEndsOn = '';
    public array $projectClientResults = [];
    public string $projectClientSelectedName = '';
    public array $availableUnits = [];

    public function mount(): void
    {
        $this->asOfDate = Carbon::now()->toDateString();

        // Set default department based on user role
        if (!$this->activeDepartmentId) {
            /** @var User|null $user */
            $user = Auth::user();
            $isOrgAdmin = $user && method_exists($user, 'hasRole')
                && $user->hasRole('Organization Admin') && !$user->hasRole('Super Admin');

            if ($isOrgAdmin && $user->person) {
                // For Org Admin: default to their first affiliated department
                $this->activeDepartmentId = PersonAffiliation::where('person_id', $user->person->id)
                    ->where('status', 'active')
                    ->whereNotNull('department_id')
                    ->value('department_id');
            }

            // Fallback: first department alphabetically
            if (!$this->activeDepartmentId) {
                $this->activeDepartmentId = Department::query()->orderBy('name')->value('id');
            }
        }
    }

    public function selectDepartment(int $departmentId): void
    {
        $this->activeDepartmentId = $departmentId;
    }

    private function buildRegistrationChartData($departmentOrganizations, Carbon $asOfDate): array
    {
        if ($departmentOrganizations->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $orgIds = $departmentOrganizations->pluck('id')->all();

        // Determine date range and grouping based on period
        switch ($this->chartPeriod) {
            case 'weekly':
                $startDate = $asOfDate->copy()->subWeeks(11)->startOfWeek();
                $dateFormat = '%x-W%v'; // ISO year-week
                $periods = collect();
                $current = $startDate->copy();
                while ($current->lte($asOfDate)) {
                    $periods->push($current->format('o-\WW'));
                    $current->addWeek();
                }
                break;
            case 'yearly':
                $startDate = $asOfDate->copy()->subYears(4)->startOfYear();
                $dateFormat = '%Y';
                $periods = collect();
                $current = $startDate->copy();
                while ($current->year <= $asOfDate->year) {
                    $periods->push($current->format('Y'));
                    $current->addYear();
                }
                break;
            default: // monthly
                $startDate = $asOfDate->copy()->subMonths(11)->startOfMonth();
                $dateFormat = '%Y-%m';
                $periods = collect();
                $current = $startDate->copy();
                while ($current->lte($asOfDate)) {
                    $periods->push($current->format('Y-m'));
                    $current->addMonth();
                }
                break;
        }

        // Query registration counts grouped by org and period
        $rows = PersonAffiliation::where('status', 'active')
            ->whereIn('organization_id', $orgIds)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $asOfDate->copy()->endOfDay())
            ->select(
                'organization_id',
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(DISTINCT person_id) as count')
            )
            ->groupBy('organization_id', 'period')
            ->get();

        // Organise into datasets per org
        $rowsByOrg = $rows->groupBy('organization_id');

        $colors = [
            'rgba(59,130,246,0.8)', 'rgba(16,185,129,0.8)', 'rgba(245,158,11,0.8)',
            'rgba(239,68,68,0.8)', 'rgba(139,92,246,0.8)', 'rgba(236,72,153,0.8)',
            'rgba(20,184,166,0.8)', 'rgba(249,115,22,0.8)', 'rgba(99,102,241,0.8)',
            'rgba(34,197,94,0.8)',
        ];

        $datasets = [];
        $colorIndex = 0;

        foreach ($departmentOrganizations as $org) {
            $orgRows = $rowsByOrg->get($org->id, collect())->keyBy('period');
            $data = $periods->map(fn($p) => (int) ($orgRows->get($p)?->count ?? 0))->values()->all();

            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $org->display_name ?: $org->legal_name,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'borderWidth' => 2,
                'tension' => 0.3,
                'fill' => false,
            ];
            $colorIndex++;
        }

        // Format labels for display
        $labels = $periods->map(function ($p) {
            if (str_contains($p, '-W')) {
                return $p; // Week format
            }
            if (strlen($p) === 4) {
                return $p; // Year
            }
            // Monthly: Y-m → Mon YYYY
            return Carbon::createFromFormat('Y-m', $p)->format('M Y');
        })->values()->all();

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    public function openCreateProject(): void
    {
        $this->resetProjectForm();
        $this->projectDepartmentId = $this->activeDepartmentId;
        $this->loadAvailableUnits();
        $this->showProjectModal = true;
    }

    public function openEditProject(int $projectId): void
    {
        $project = Project::with(['client'])->find($projectId);
        if (!$project) {
            return;
        }

        $this->editingProjectId = $projectId;
        $this->projectName = $project->name ?? '';
        $this->projectCode = $project->code ?? '';
        $this->projectDescription = $project->description ?? '';
        $this->projectDepartmentId = $project->department_id;
        $this->projectOrganizationUnitId = $project->organization_unit_id;
        $this->projectClientPersonId = $project->client_person_id;
        $this->projectClientSelectedName = $project->client?->full_name ?? '';
        $this->projectClientSearch = $this->projectClientSelectedName;
        $this->projectExternalClientName = $project->external_client_name ?? '';
        $this->projectIsActive = (bool) $project->is_active;
        $this->projectStartsOn = $project->starts_on?->format('Y-m-d') ?? '';
        $this->projectEndsOn = $project->ends_on?->format('Y-m-d') ?? '';
        $this->loadAvailableUnits();
        $this->showProjectModal = true;
    }

    public function updatedProjectClientSearch(): void
    {
        if (strlen($this->projectClientSearch) < 2) {
            $this->projectClientResults = [];
            return;
        }

        $this->projectClientResults = Person::query()
            ->where(function ($q) {
                $q->where('given_name', 'like', "%{$this->projectClientSearch}%")
                  ->orWhere('family_name', 'like', "%{$this->projectClientSearch}%")
                  ->orWhere('person_id', 'like', "%{$this->projectClientSearch}%");
            })
            ->limit(8)
            ->get(['id', 'given_name', 'family_name', 'person_id'])
            ->toArray();
    }

    public function selectProjectClient(int $personId, string $name): void
    {
        $this->projectClientPersonId = $personId;
        $this->projectClientSelectedName = $name;
        $this->projectClientSearch = $name;
        $this->projectClientResults = [];
    }

    public function clearProjectClient(): void
    {
        $this->projectClientPersonId = null;
        $this->projectClientSelectedName = '';
        $this->projectClientSearch = '';
        $this->projectClientResults = [];
    }

    public function updatedProjectDepartmentId(): void
    {
        $this->projectOrganizationUnitId = null;
        $this->loadAvailableUnits();
    }

    private function loadAvailableUnits(): void
    {
        if (!$this->projectDepartmentId) {
            $this->availableUnits = [];
            return;
        }

        $this->availableUnits = OrganizationUnit::query()
            ->where('department_id', $this->projectDepartmentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    public function saveProject(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user?->can('edit-projects') || $user?->can('create-projects'), 403);

        $this->validate([
            'projectName'            => 'required|string|max:255',
            'projectCode'            => 'nullable|string|max:50',
            'projectDescription'     => 'nullable|string',
            'projectDepartmentId'    => 'required|exists:departments,id',
            // The unit must belong to the selected department, not merely
            // exist — otherwise a crafted request can attach the project to
            // a unit from another department/organization.
            'projectOrganizationUnitId' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('organization_units', 'id')
                    ->where('department_id', $this->projectDepartmentId),
            ],
            'projectClientPersonId'  => 'nullable|exists:persons,id',
            'projectExternalClientName' => 'nullable|string|max:255',
            'projectStartsOn'        => 'nullable|date',
            'projectEndsOn'          => 'nullable|date|after_or_equal:projectStartsOn',
        ]);

        // Non-super users may only write projects in departments they manage.
        if (!$user->hasRole('Super Admin')) {
            abort_unless(
                $user->managedDepartmentIds()->contains((int) $this->projectDepartmentId),
                403
            );
        }

        $data = [
            'name'                  => $this->projectName,
            'code'                  => $this->projectCode ?: null,
            'description'           => $this->projectDescription ?: null,
            'department_id'         => $this->projectDepartmentId,
            'organization_unit_id'  => $this->projectOrganizationUnitId,
            'client_person_id'      => $this->projectClientPersonId,
            'external_client_name'  => $this->projectClientPersonId ? null : ($this->projectExternalClientName ?: null),
            'is_active'             => $this->projectIsActive,
            'starts_on'             => $this->projectStartsOn ?: null,
            'ends_on'               => $this->projectEndsOn ?: null,
        ];

        if ($this->editingProjectId) {
            $project = Project::find($this->editingProjectId);

            // The project being edited must also be inside the user's scope.
            if ($project && !$user->hasRole('Super Admin')) {
                abort_unless(
                    $user->managedDepartmentIds()->contains($project->department_id),
                    403
                );
            }

            $project?->update($data);
        } else {
            Project::create($data);
        }

        $this->closeProjectModal();
    }

    public function closeProjectModal(): void
    {
        $this->showProjectModal = false;
        $this->resetProjectForm();
    }

    private function resetProjectForm(): void
    {
        $this->editingProjectId = null;
        $this->projectName = '';
        $this->projectCode = '';
        $this->projectDescription = '';
        $this->projectDepartmentId = $this->activeDepartmentId;
        $this->projectOrganizationUnitId = null;
        $this->projectClientPersonId = null;
        $this->projectClientSelectedName = '';
        $this->projectClientSearch = '';
        $this->projectExternalClientName = '';
        $this->projectIsActive = true;
        $this->projectStartsOn = '';
        $this->projectEndsOn = '';
        $this->projectClientResults = [];
        $this->availableUnits = [];
    }

    public function render()
    {
        /** @var User|null $user */
        $user = Auth::user();

        $canViewDepartments = (bool) $user
            && ((method_exists($user, 'can') && $user->can('view-departments-dashboard'))
                || (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('Organization Admin'))));

        abort_unless($canViewDepartments, 403);

        $isSuperAdmin = (bool) $user && method_exists($user, 'hasRole') && $user->hasRole('Super Admin');
        $isOrgAdmin = (bool) $user && method_exists($user, 'hasRole')
            && $user->hasRole('Organization Admin') && !$user->hasRole('Super Admin');

        // For Org Admin: scope to affiliated departments only
        $affiliatedDepartmentIds = collect();
        if ($isOrgAdmin && $user->person) {
            $affiliatedDepartmentIds = PersonAffiliation::where('person_id', $user->person->id)
                ->where('status', 'active')
                ->whereNotNull('department_id')
                ->pluck('department_id')
                ->unique();
        }

        $departmentsQuery = Department::query()
            ->with(['organization:id,legal_name', 'admin:id,name', 'subCategories:id,department_id,name,is_active'])
            ->withCount(['projects', 'subCategories'])
            ->orderBy('name');

        // Org Admin only sees their affiliated departments
        if ($isOrgAdmin) {
            $departmentsQuery->whereIn('id', $affiliatedDepartmentIds);
        }

        $departments = $departmentsQuery->get();

        $ankoleDepartments = $departments->filter(function ($department) {
            $organizationName = strtolower(trim((string) ($department->organization?->legal_name ?? '')));

            return $organizationName === 'ankole diocese';
        })->values();

        $nonAnkoleDepartments = $departments->filter(function ($department) {
            $organizationName = strtolower(trim((string) ($department->organization?->legal_name ?? '')));

            return $organizationName !== 'ankole diocese';
        })->values();

        if (!$this->activeDepartmentId && $departments->isNotEmpty()) {
            $this->activeDepartmentId = (int) $departments->first()->id;
        }

        $selectedDepartment = $departments->firstWhere('id', (int) $this->activeDepartmentId);

        // If selected department is not in the available list, reset to first available
        if (!$selectedDepartment && $departments->isNotEmpty()) {
            $this->activeDepartmentId = (int) $departments->first()->id;
            $selectedDepartment = $departments->first();
        }

        // Dynamic: get sub-category names for the selected department
        $subCategoryNames = $selectedDepartment
            ? $selectedDepartment->subCategories->pluck('name')->map(fn($n) => strtolower(trim($n)))->filter()->values()
            : collect();

        $hasSubCategories = $subCategoryNames->isNotEmpty();

        // Sub-category matching must stay inside the department's own
        // organization tree; category names alone would match organizations
        // from unrelated dioceses.
        $orgTreeIds = $selectedDepartment?->organization?->subtreeIds() ?? collect();

        // Find organizations whose category matches any sub-category of the selected department
        $departmentOrganizations = collect();

        if ($hasSubCategories) {
            $departmentOrganizations = Organization::query()
                ->select(['id', 'legal_name', 'display_name', 'category'])
                ->where('is_super', false)
                ->whereIn('id', $orgTreeIds)
                ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all())
                ->orderBy('legal_name')
                ->get();
        }

        // Count persons by department_id from person_affiliations
        // Persons are affiliated with the super org but linked to departments via department_id
        $departmentPersonsCount = 0;

        if ($selectedDepartment) {
            // Total persons in this department
            $departmentPersonsCount = PersonAffiliation::where('department_id', $this->activeDepartmentId)
                ->where('status', 'active')
                ->distinct('person_id')
                ->count('person_id');

            // Per-organization person counts: count persons whose organization_id matches each org
            // OR whose department_id matches the selected department (since persons are linked to super org)
            if ($departmentOrganizations->isNotEmpty()) {
                $orgIds = $departmentOrganizations->pluck('id')->all();

                // Count persons directly affiliated with each org
                $directCounts = PersonAffiliation::where('status', 'active')
                    ->whereIn('organization_id', $orgIds)
                    ->select('organization_id', DB::raw('COUNT(DISTINCT person_id) as persons_count'))
                    ->groupBy('organization_id')
                    ->pluck('persons_count', 'organization_id');

                // For orgs with no direct affiliations, use department-based count
                foreach ($departmentOrganizations as $org) {
                    $org->persons_count = $directCounts->get($org->id, 0);
                }

                // If no org has direct affiliations, fall back to department count for all
                if ($directCounts->sum() === 0 && $departmentPersonsCount > 0) {
                    foreach ($departmentOrganizations as $org) {
                        $org->persons_count = $departmentPersonsCount;
                    }
                }
            }
        }

        $projectEagerLoad = [
            'admin:id,name',
            'departmentSubCategory:id,department_id,name',
            'projectDepartments:id,project_id,name,is_active',
            'department.organization:id,legal_name,display_name,category',
            'client:id,given_name,family_name',
            'organizationUnit:id,name,code',
        ];

        $selectedDepartmentProjects = $selectedDepartment
            ? ($hasSubCategories
                ? \App\Models\Project::query()
                    ->with($projectEagerLoad)
                    ->whereHas('department', function ($query) use ($subCategoryNames, $orgTreeIds) {
                        $query->where('id', $this->activeDepartmentId)
                            ->orWhereHas('organization', function ($orgQuery) use ($subCategoryNames, $orgTreeIds) {
                                $orgQuery->whereIn('id', $orgTreeIds)
                                    ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all());
                            });
                    })
                    ->orderBy('name')
                    ->get()
                : $selectedDepartment->projects()
                    ->with($projectEagerLoad)
                    ->orderBy('name')
                    ->get())
            : collect();

        // Add person counts to each project based on its department
        if ($selectedDepartmentProjects->isNotEmpty()) {
            $projectDeptIds = $selectedDepartmentProjects->pluck('department_id')->unique()->filter()->all();
            $personsPerDept = PersonAffiliation::where('status', 'active')
                ->whereIn('department_id', $projectDeptIds)
                ->select('department_id', DB::raw('COUNT(DISTINCT person_id) as persons_count'))
                ->groupBy('department_id')
                ->pluck('persons_count', 'department_id');

            foreach ($selectedDepartmentProjects as $project) {
                $project->persons_count = $personsPerDept->get($project->department_id, 0);
            }
        }

        $asOfDate = Carbon::parse($this->asOfDate ?: Carbon::now()->toDateString())->startOfDay();

        $selectedDepartmentStats = [
            'total_projects' => $selectedDepartmentProjects->count(),
            'active_projects' => $selectedDepartmentProjects->where('is_active', true)->count(),
            'inactive_projects' => $selectedDepartmentProjects->where('is_active', false)->count(),
            'ongoing_projects' => $selectedDepartmentProjects->filter(function ($project) use ($asOfDate) {
                if (!$project->starts_on) {
                    return false;
                }

                $startsOn = Carbon::parse($project->starts_on)->startOfDay();
                $endsOn = $project->ends_on ? Carbon::parse($project->ends_on)->startOfDay() : null;

                return $startsOn->lessThanOrEqualTo($asOfDate)
                    && (!$endsOn || $endsOn->greaterThanOrEqualTo($asOfDate));
            })->count(),
            'completed_projects' => $selectedDepartmentProjects->filter(function ($project) use ($asOfDate) {
                return $project->ends_on && Carbon::parse($project->ends_on)->startOfDay()->lessThan($asOfDate);
            })->count(),
            'upcoming_projects' => $selectedDepartmentProjects->filter(function ($project) use ($asOfDate) {
                return $project->starts_on && Carbon::parse($project->starts_on)->startOfDay()->greaterThan($asOfDate);
            })->count(),
            'project_departments_count' => $selectedDepartmentProjects->sum(function ($project) {
                return $project->projectDepartments->count();
            }),
            'sub_category_coverage' => $selectedDepartmentProjects
                ->pluck('departmentSubCategory.name')
                ->filter()
                ->unique()
                ->count(),
            'recent_projects' => $selectedDepartmentProjects
                ->sortByDesc('created_at')
                ->take(5)
                ->values(),
            'total_persons' => $departmentPersonsCount,
            'total_organizations' => $departmentOrganizations->count(),
            'total_org_persons' => $departmentOrganizations->sum('persons_count'),
        ];

        $selectedDepartmentStats['completion_rate'] = $selectedDepartmentStats['total_projects'] > 0
            ? (int) round(($selectedDepartmentStats['completed_projects'] / $selectedDepartmentStats['total_projects']) * 100)
            : 0;

        $summary = [
            'total_departments' => $departments->count(),
            'active_departments' => $departments->where('is_active', true)->count(),
            'departments_with_admin' => $departments->whereNotNull('admin_user_id')->count(),
            'total_projects' => $departments->sum('projects_count'),
        ];

        // Build registration trend chart data per project (organization)
        $registrationChartData = $this->buildRegistrationChartData($departmentOrganizations, $asOfDate);

        // Chartable projects (projects with persons)
$chartableProjects = $selectedDepartmentProjects
    ->filter(fn($project) => ($project->persons_count ?? 0) > 0)
    ->sortByDesc('persons_count')
    ->values();

// Selected chart project
$selectedChartProject = ($this->chartViewMode === 'single' && $this->selectedChartProjectId)
    ? $selectedDepartmentProjects->firstWhere('id', $this->selectedChartProjectId)
    : null;

// Override chart data if in single mode
if ($this->chartViewMode === 'single' && $this->selectedChartProjectId) {
    $singleOrg = $departmentOrganizations->first(function ($org) use ($selectedDepartmentProjects) {
        $project = $selectedDepartmentProjects->firstWhere('id', $this->selectedChartProjectId);
        return $project && $org->id === $project->department?->organization_id;
    });
    
    if ($singleOrg) {
        $registrationChartData = $this->buildRegistrationChartData(collect([$singleOrg]), $asOfDate);
    }
}

        return view('livewire.departments.departments-dashboard', [
            'departments' => $departments,
            'summary' => $summary,
            'selectedDepartment' => $selectedDepartment,
            'selectedDepartmentProjects' => $selectedDepartmentProjects,
            'selectedDepartmentStats' => $selectedDepartmentStats,
            'hasSubCategories' => $hasSubCategories,
            'departmentOrganizations' => $departmentOrganizations,
            'isSuperAdmin' => $isSuperAdmin,
            'isOrgAdmin' => $isOrgAdmin,
            'ankoleDepartments' => $ankoleDepartments,
            'nonAnkoleDepartments' => $nonAnkoleDepartments,
            'registrationChartData' => $registrationChartData,
            'chartableProjects' => $chartableProjects,           // ADD
    'selectedChartProject' => $selectedChartProject,     // ADD
        ]);
    }

    public function showOrganizationPersons($organizationId)
    {
        $persons = [];
        $projects = \App\Models\Project::whereHas('department.organization', function ($q) use ($organizationId) {
            $q->where('id', $organizationId);
        })->get();
        foreach ($projects as $project) {
            foreach ($project->persons as $person) {
                $persons[] = $person;
            }
        }
        $this->selectedOrganizationPersons = collect($persons)->unique('id')->values()->all();
    }

 
 
/**
 * Handle clicking the chart icon in the projects table
 */
public function viewProjectChart(int $projectId): void
{
    $this->chartViewMode = 'single';
    $this->selectedChartProjectId = $projectId;
}

/**
 * Reset chart view to all projects
 */
public function resetChartView(): void
{
    $this->chartViewMode = 'all';
    $this->selectedChartProjectId = null;
}

public function setChartPeriod(string $period): void
{
    $this->chartPeriod = $period;
}

public function updatedActiveDepartmentId(): void
{
    $this->selectedChartProjectId = null;
    $this->chartViewMode = 'all';
}
}
