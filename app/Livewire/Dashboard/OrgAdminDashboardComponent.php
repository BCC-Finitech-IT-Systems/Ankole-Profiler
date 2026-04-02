<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Person;
use App\Models\Organization;
use App\Models\Department;
use App\Models\PersonAffiliation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrgAdminDashboardComponent extends Component
{
    // User context
    public $currentUser;
    public $isSuperAdmin = false;
    public $isBishop = false;

    // View mode: 'overview', 'department', 'organization'
    public $viewMode = 'overview';

    // Filters
    public $dateRange = '30';
    public $startDate;
    public $endDate;
    public $chartPeriod = 'monthly';

    // Selected items for drill-down
    public $selectedDepartmentId = null;
    public $selectedOrganizationId = null;
    public $selectedDepartment = null;
    public $selectedOrganization = null;

    // Data
    public $overviewStats = [];
    public $departmentStats = [];
    public $organizationStats = [];
    public $genderDistribution = [];
    public $ageDistribution = [];
    public $geographicDistribution = [];
    public $monthlyRegistrations = [];
    public $recentActivity = [];
    public $topOrganizations = [];
    public $registrationChartData = [];

    // Department-specific data
    public $departmentOrganizations = [];
    public $departmentPersons = [];

    // Organization-specific data
    public $organizationPersons = [];

    // UI State
    public $isLoading = true;
    public $showPersonsModal = false;
    public $modalPersons = [];
    public $modalTitle = '';

    protected $queryString = [
        'viewMode' => ['except' => 'overview'],
        'selectedDepartmentId' => ['except' => null, 'as' => 'dept'],
        'selectedOrganizationId' => ['except' => null, 'as' => 'org'],
        'dateRange' => ['except' => '30'],
    ];

    protected $listeners = ['refreshDashboard' => 'loadDashboardData'];

    public function mount()
    {
        $this->currentUser = Auth::user();
        $this->checkUserRoles();
        $this->setDefaultDates();
        $this->loadDashboardData();
    }

    public function updatedDateRange()
    {
        $this->setDefaultDates();
        $this->loadDashboardData();
    }

    public function updatedChartPeriod()
    {
        $this->loadDashboardData();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;

        if ($mode === 'overview') {
            $this->selectedDepartmentId = null;
            $this->selectedOrganizationId = null;
            $this->selectedDepartment = null;
            $this->selectedOrganization = null;
        }

        $this->loadDashboardData();
    }

    public function selectDepartment($departmentId)
    {
        $this->selectedDepartmentId = $departmentId;
        $this->selectedOrganizationId = null;
        $this->selectedDepartment = Department::with('subCategories')->find($departmentId);
        $this->viewMode = 'department';
        $this->loadDashboardData();
    }

    public function selectOrganization($organizationId)
    {
        $this->selectedOrganizationId = $organizationId;
        $this->selectedOrganization = Organization::find($organizationId);
        $this->viewMode = 'organization';
        $this->loadDashboardData();
    }

    public function backToOverview()
    {
        $this->setViewMode('overview');
    }

    public function backToDepartment()
    {
        $this->selectedOrganizationId = null;
        $this->selectedOrganization = null;
        $this->viewMode = 'department';
        $this->loadDashboardData();
    }

    public function showPersons($type, $id = null)
    {
        $this->modalPersons = [];

        switch ($type) {
            case 'department':
                $this->modalTitle = 'Persons in ' . ($this->selectedDepartment?->name ?? 'Department');
                $this->modalPersons = $this->getPersonsByDepartment($id ?? $this->selectedDepartmentId);
                break;
            case 'organization':
                $org = Organization::find($id ?? $this->selectedOrganizationId);
                $this->modalTitle = 'Persons in ' . ($org?->display_name ?? $org?->legal_name ?? 'Organization');
                $this->modalPersons = $this->getPersonsByOrganization($id ?? $this->selectedOrganizationId);
                break;
            case 'new':
                $this->modalTitle = 'New Registrations';
                $this->modalPersons = $this->getNewPersons();
                break;
        }

        $this->showPersonsModal = true;
    }

    public function closePersonsModal()
    {
        $this->showPersonsModal = false;
        $this->modalPersons = [];
    }

    public function refreshData()
    {
        $cacheKey = $this->getCacheKey();
        Cache::forget($cacheKey);
        $this->loadDashboardData();
    }

    public function render()
    {
        return view('livewire.dashboard.org-admin-dashboard-component');
    }

    private function checkUserRoles()
    {
        if ($this->currentUser && method_exists($this->currentUser, 'hasRole')) {
            $this->isSuperAdmin = $this->currentUser->hasRole('Super Admin');
            $this->isBishop = $this->currentUser->hasRole('Bishop') || $this->isSuperAdmin;
        }
    }

    private function setDefaultDates()
    {
        $this->endDate = Carbon::now()->endOfDay();

        switch ($this->dateRange) {
            case '7':
                $this->startDate = Carbon::now()->subDays(7)->startOfDay();
                break;
            case '30':
                $this->startDate = Carbon::now()->subDays(30)->startOfDay();
                break;
            case '90':
                $this->startDate = Carbon::now()->subDays(90)->startOfDay();
                break;
            case '365':
                $this->startDate = Carbon::now()->subYear()->startOfDay();
                break;
            case 'all':
                $this->startDate = Carbon::createFromDate(2000, 1, 1);
                break;
            default:
                $this->startDate = Carbon::now()->subDays(30)->startOfDay();
        }
    }

    private function getCacheKey()
    {
        return 'admin_dashboard_' . $this->currentUser->id . '_' . $this->viewMode . '_' .
               $this->dateRange . '_' . $this->selectedDepartmentId . '_' . $this->selectedOrganizationId;
    }

    public function loadDashboardData()
    {
        $this->isLoading = true;

        try {
            switch ($this->viewMode) {
                case 'department':
                    $this->loadDepartmentView();
                    break;
                case 'organization':
                    $this->loadOrganizationView();
                    break;
                default:
                    $this->loadOverviewData();
            }

            // Always load recent activity (not cached)
            $this->recentActivity = $this->getRecentActivity();

        } catch (\Exception $e) {
            Log::error('Dashboard loading error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isLoading = false;
    }

    private function loadOverviewData()
    {
        $cacheKey = $this->getCacheKey();

        $data = Cache::remember($cacheKey, 300, function () {
            return [
                'overview' => $this->calculateOverviewStats(),
                'departments' => $this->calculateDepartmentStats(),
                'topOrganizations' => $this->calculateTopOrganizations(),
                'gender' => $this->calculateGenderDistribution(),
                'age' => $this->calculateAgeDistribution(),
                'geographic' => $this->calculateGeographicDistribution(),
                'monthly' => $this->calculateMonthlyRegistrations(),
            ];
        });

        $this->overviewStats = $data['overview'];
        $this->departmentStats = $data['departments'];
        $this->topOrganizations = $data['topOrganizations'];
        $this->genderDistribution = $data['gender'];
        $this->ageDistribution = $data['age'];
        $this->geographicDistribution = $data['geographic'];
        $this->monthlyRegistrations = $data['monthly'];
    }

    private function loadDepartmentView()
    {
        if (!$this->selectedDepartmentId) {
            $this->setViewMode('overview');
            return;
        }

        $this->selectedDepartment = Department::with('subCategories')->find($this->selectedDepartmentId);

        if (!$this->selectedDepartment) {
            $this->setViewMode('overview');
            return;
        }

        // Get sub-category names for matching organizations
        $subCategoryNames = $this->selectedDepartment->subCategories
            ->pluck('name')
            ->map(fn($n) => strtolower(trim($n)))
            ->filter()
            ->values();

        // Get organizations in this department
        $this->departmentOrganizations = [];
        if ($subCategoryNames->isNotEmpty()) {
            $this->departmentOrganizations = Organization::query()
                ->where('is_super', false)
                ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all())
                ->withCount([
                    'persons as total_persons',
                    'persons as new_persons' => function ($q) {
                        $q->whereBetween('created_at', [$this->startDate, $this->endDate]);
                    },
                    'persons as active_persons' => function ($q) {
                        $q->whereHas('affiliations', fn($a) => $a->where('status', 'active'));
                    },
                ])
                ->orderByDesc('total_persons')
                ->get()
                ->map(function ($org) {
                    return [
                        'id' => $org->id,
                        'name' => $org->display_name ?? $org->legal_name,
                        'category' => $org->category,
                        'total_persons' => $org->total_persons,
                        'new_persons' => $org->new_persons,
                        'active_persons' => $org->active_persons,
                        'status' => $org->status ?? 'active',
                    ];
                })
                ->toArray();
        }

        // Calculate department-level stats
        $orgIds = collect($this->departmentOrganizations)->pluck('id')->toArray();

        // Also count by department_id in affiliations
        $deptPersonsCount = PersonAffiliation::where('department_id', $this->selectedDepartmentId)
            ->where('status', 'active')
            ->distinct('person_id')
            ->count('person_id');

        $orgPersonsCount = !empty($orgIds)
            ? PersonAffiliation::whereIn('organization_id', $orgIds)
                ->where('status', 'active')
                ->distinct('person_id')
                ->count('person_id')
            : 0;

        $newDeptPersons = PersonAffiliation::where('department_id', $this->selectedDepartmentId)
            ->where('status', 'active')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('person_id')
            ->count('person_id');

        $this->overviewStats = [
            'total_persons' => max($deptPersonsCount, $orgPersonsCount),
            'new_persons' => $newDeptPersons,
            'total_organizations' => count($this->departmentOrganizations),
            'active_organizations' => collect($this->departmentOrganizations)->where('status', 'active')->count(),
            'total_sub_categories' => $this->selectedDepartment->subCategories->count(),
        ];

        // Gender distribution for department
        $this->genderDistribution = $this->calculateGenderDistribution($this->selectedDepartmentId, $orgIds);

        // Monthly registrations for department
        $this->monthlyRegistrations = $this->calculateMonthlyRegistrations($this->selectedDepartmentId, $orgIds);

        // Registration chart data per organization
        $this->registrationChartData = $this->buildRegistrationChartData($orgIds);
    }

    private function loadOrganizationView()
    {
        if (!$this->selectedOrganizationId) {
            $this->backToDepartment();
            return;
        }

        $this->selectedOrganization = Organization::withCount([
            'persons as total_persons',
            'persons as new_persons' => function ($q) {
                $q->whereBetween('created_at', [$this->startDate, $this->endDate]);
            },
            'persons as active_persons' => function ($q) {
                $q->whereHas('affiliations', fn($a) => $a->where('status', 'active'));
            },
        ])->find($this->selectedOrganizationId);

        if (!$this->selectedOrganization) {
            $this->backToDepartment();
            return;
        }

        // Get Project Heads for this organization
        $projectHeads = User::role('Project Head')
            ->whereHas('person.affiliations', function ($q) {
                $q->where('organization_id', $this->selectedOrganizationId)
                    ->where('status', 'active');
            })
            ->with('person')
            ->get();

        $this->overviewStats = [
            'total_persons' => $this->selectedOrganization->total_persons,
            'new_persons' => $this->selectedOrganization->new_persons,
            'active_persons' => $this->selectedOrganization->active_persons,
            'project_heads' => $projectHeads->count(),
            'project_heads_list' => $projectHeads->map(function ($ph) {
                return [
                    'id' => $ph->id,
                    'name' => $ph->name,
                    'email' => $ph->email,
                ];
            })->toArray(),
        ];

        // Gender distribution for organization
        $this->genderDistribution = $this->calculateGenderDistribution(null, [$this->selectedOrganizationId]);

        // Age distribution for organization
        $this->ageDistribution = $this->calculateAgeDistribution(null, [$this->selectedOrganizationId]);

        // Geographic distribution for organization
        $this->geographicDistribution = $this->calculateGeographicDistribution(null, [$this->selectedOrganizationId]);

        // Monthly registrations for organization
        $this->monthlyRegistrations = $this->calculateMonthlyRegistrations(null, [$this->selectedOrganizationId]);

        // Recent persons in this organization
        $this->organizationPersons = Person::with(['user', 'affiliations'])
            ->whereHas('affiliations', function ($q) {
                $q->where('organization_id', $this->selectedOrganizationId);
            })
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->given_name . ' ' . $person->family_name,
                    'email' => $person->user?->email,
                    'gender' => $person->gender,
                    'phone' => $person->phone_number,
                    'created_at' => $person->created_at->format('M d, Y'),
                    'created_ago' => $person->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    private function calculateOverviewStats(): array
    {
        // Total persons (unique by person_id in affiliations)
        $totalPersons = PersonAffiliation::where('status', 'active')
            ->distinct('person_id')
            ->count('person_id');

        $activePersons = $totalPersons; // All counted are active

        // New persons in date range
        $newPersons = Person::whereBetween('created_at', [$this->startDate, $this->endDate])->count();

        // Organizations
        $totalOrganizations = Organization::where('is_super', false)->count();
        $activeOrganizations = Organization::where('is_super', false)->where('status', 'active')->count();
        $newOrganizations = Organization::where('is_super', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();

        // Departments
        $totalDepartments = Department::count();

        // Users
        $usersWithAccounts = User::whereNotNull('email_verified_at')->count();
        $pendingVerification = User::whereNull('email_verified_at')->count();

        // Role counts
        $projectHeadsCount = User::role('Project Head')->count();
        $orgAdminsCount = User::role('Organization Admin')->count();

        // Growth calculation
        $previousPeriodStart = Carbon::parse($this->startDate)->subDays($this->startDate->diffInDays($this->endDate));
        $previousPeriodEnd = Carbon::parse($this->startDate)->subDay();

        $previousPersons = Person::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();

        $personGrowth = $previousPersons > 0
            ? round((($newPersons - $previousPersons) / $previousPersons) * 100, 1)
            : ($newPersons > 0 ? 100 : 0);

        return [
            'total_persons' => $totalPersons,
            'active_persons' => $activePersons,
            'new_persons' => $newPersons,
            'person_growth' => $personGrowth,
            'total_organizations' => $totalOrganizations,
            'active_organizations' => $activeOrganizations,
            'new_organizations' => $newOrganizations,
            'total_departments' => $totalDepartments,
            'users_with_accounts' => $usersWithAccounts,
            'pending_verification' => $pendingVerification,
            'project_heads' => $projectHeadsCount,
            'org_admins' => $orgAdminsCount,
        ];
    }

    private function calculateDepartmentStats(): array
    {
        $departments = Department::with('subCategories')->orderBy('name')->get();

        return $departments->map(function ($dept) {
            // Get sub-category names
            $subCategoryNames = $dept->subCategories
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->filter()
                ->values();

            // Count persons by department_id
            $deptPersonsCount = PersonAffiliation::where('department_id', $dept->id)
                ->where('status', 'active')
                ->distinct('person_id')
                ->count('person_id');

            // Count organizations
            $orgCount = 0;
            $orgPersonsCount = 0;
            if ($subCategoryNames->isNotEmpty()) {
                $orgs = Organization::where('is_super', false)
                    ->whereRaw('LOWER(TRIM(category)) IN (' . $subCategoryNames->map(fn() => '?')->join(',') . ')', $subCategoryNames->all())
                    ->withCount('persons')
                    ->get();

                $orgCount = $orgs->count();
                $orgPersonsCount = $orgs->sum('persons_count');
            }

            // New persons in period
            $newPersons = PersonAffiliation::where('department_id', $dept->id)
                ->where('status', 'active')
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->distinct('person_id')
                ->count('person_id');

            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'total_persons' => max($deptPersonsCount, $orgPersonsCount),
                'new_persons' => $newPersons,
                'total_organizations' => $orgCount,
                'total_sub_categories' => $dept->subCategories->count(),
                'is_active' => $dept->is_active ?? true,
            ];
        })
        ->sortByDesc('total_persons')
        ->values()
        ->toArray();
    }

    private function calculateTopOrganizations(): array
    {
        return Organization::where('is_super', false)
            ->withCount([
                'persons as total_persons',
                'persons as new_persons' => function ($q) {
                    $q->whereBetween('created_at', [$this->startDate, $this->endDate]);
                },
            ])
            ->having('total_persons', '>', 0)
            ->orderByDesc('total_persons')
            ->limit(15)
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->display_name ?? $org->legal_name,
                    'category' => $org->category,
                    'total_persons' => $org->total_persons,
                    'new_persons' => $org->new_persons,
                ];
            })
            ->toArray();
    }

    private function calculateGenderDistribution($departmentId = null, $orgIds = null): array
    {
        $query = Person::query();

        if ($departmentId) {
            $query->whereHas('affiliations', fn($q) => $q->where('department_id', $departmentId)->where('status', 'active'));
        } elseif ($orgIds && !empty($orgIds)) {
            $query->whereHas('affiliations', fn($q) => $q->whereIn('organization_id', $orgIds)->where('status', 'active'));
        }

        $distribution = $query->select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(fn($item) => [strtolower($item->gender) => $item->count])
            ->toArray();

        $total = array_sum($distribution);

        return [
            'male' => $distribution['male'] ?? 0,
            'female' => $distribution['female'] ?? 0,
            'other' => $distribution['other'] ?? 0,
            'total' => $total,
            'male_percentage' => $total > 0 ? round(($distribution['male'] ?? 0) / $total * 100, 1) : 0,
            'female_percentage' => $total > 0 ? round(($distribution['female'] ?? 0) / $total * 100, 1) : 0,
        ];
    }

    private function calculateAgeDistribution($departmentId = null, $orgIds = null): array
    {
        $query = Person::query()->whereNotNull('date_of_birth');

        if ($departmentId) {
            $query->whereHas('affiliations', fn($q) => $q->where('department_id', $departmentId)->where('status', 'active'));
        } elseif ($orgIds && !empty($orgIds)) {
            $query->whereHas('affiliations', fn($q) => $q->whereIn('organization_id', $orgIds)->where('status', 'active'));
        }

        $persons = $query->select('date_of_birth')->get();

        $ageGroups = [
            '0-17' => 0,
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56-65' => 0,
            '65+' => 0,
        ];

        foreach ($persons as $person) {
            $age = Carbon::parse($person->date_of_birth)->age;

            if ($age < 18) $ageGroups['0-17']++;
            elseif ($age <= 25) $ageGroups['18-25']++;
            elseif ($age <= 35) $ageGroups['26-35']++;
            elseif ($age <= 45) $ageGroups['36-45']++;
            elseif ($age <= 55) $ageGroups['46-55']++;
            elseif ($age <= 65) $ageGroups['56-65']++;
            else $ageGroups['65+']++;
        }

        return $ageGroups;
    }

    private function calculateGeographicDistribution($departmentId = null, $orgIds = null): array
    {
        $query = Person::query();

        if ($departmentId) {
            $query->whereHas('affiliations', fn($q) => $q->where('department_id', $departmentId)->where('status', 'active'));
        } elseif ($orgIds && !empty($orgIds)) {
            $query->whereHas('affiliations', fn($q) => $q->whereIn('organization_id', $orgIds)->where('status', 'active'));
        }

        $byDistrict = (clone $query)
            ->select('district', DB::raw('count(*) as count'))
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->groupBy('district')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($item) => [$item->district => $item->count])
            ->toArray();

        $byCity = (clone $query)
            ->select('city', DB::raw('count(*) as count'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($item) => [$item->city => $item->count])
            ->toArray();

        return [
            'by_district' => $byDistrict,
            'by_city' => $byCity,
        ];
    }

    private function calculateMonthlyRegistrations($departmentId = null, $orgIds = null): array
    {
        $months = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'month' => $date->format('M Y'),
                'month_key' => $date->format('Y-m'),
            ]);
        }

        $query = PersonAffiliation::where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        } elseif ($orgIds && !empty($orgIds)) {
            $query->whereIn('organization_id', $orgIds);
        }

        $registrations = $query
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"), DB::raw('count(DISTINCT person_id) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month_key')
            ->get()
            ->mapWithKeys(fn($item) => [$item->month_key => $item->count])
            ->toArray();

        return $months->map(function ($month) use ($registrations) {
            return [
                'month' => $month['month'],
                'count' => $registrations[$month['month_key']] ?? 0,
            ];
        })->toArray();
    }

    private function buildRegistrationChartData($orgIds): array
    {
        if (empty($orgIds)) {
            return ['labels' => [], 'datasets' => []];
        }

        $organizations = Organization::whereIn('id', $orgIds)->get();

        // Determine date range based on chart period
        switch ($this->chartPeriod) {
            case 'weekly':
                $startDate = Carbon::now()->subWeeks(11)->startOfWeek();
                $dateFormat = '%x-W%v';
                $periods = collect();
                $current = $startDate->copy();
                while ($current->lte(Carbon::now())) {
                    $periods->push($current->format('o-\WW'));
                    $current->addWeek();
                }
                break;
            case 'yearly':
                $startDate = Carbon::now()->subYears(4)->startOfYear();
                $dateFormat = '%Y';
                $periods = collect();
                $current = $startDate->copy();
                while ($current->year <= Carbon::now()->year) {
                    $periods->push($current->format('Y'));
                    $current->addYear();
                }
                break;
            default: // monthly
                $startDate = Carbon::now()->subMonths(11)->startOfMonth();
                $dateFormat = '%Y-%m';
                $periods = collect();
                $current = $startDate->copy();
                while ($current->lte(Carbon::now())) {
                    $periods->push($current->format('Y-m'));
                    $current->addMonth();
                }
                break;
        }

        // Query registration counts
        $rows = PersonAffiliation::where('status', 'active')
            ->whereIn('organization_id', $orgIds)
            ->where('created_at', '>=', $startDate)
            ->select(
                'organization_id',
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(DISTINCT person_id) as count')
            )
            ->groupBy('organization_id', 'period')
            ->get();

        $rowsByOrg = $rows->groupBy('organization_id');

        $colors = [
            'rgba(59,130,246,0.8)', 'rgba(16,185,129,0.8)', 'rgba(245,158,11,0.8)',
            'rgba(239,68,68,0.8)', 'rgba(139,92,246,0.8)', 'rgba(236,72,153,0.8)',
            'rgba(20,184,166,0.8)', 'rgba(249,115,22,0.8)', 'rgba(99,102,241,0.8)',
            'rgba(34,197,94,0.8)',
        ];

        $datasets = [];
        $colorIndex = 0;

        foreach ($organizations as $org) {
            $orgRows = $rowsByOrg->get($org->id, collect())->keyBy('period');
            $data = $periods->map(fn($p) => (int) ($orgRows->get($p)?->count ?? 0))->values()->all();

            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $org->display_name ?: $org->legal_name,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
            $colorIndex++;
        }

        $labels = $periods->map(function ($p) {
            if (str_contains($p, '-W')) return $p;
            if (strlen($p) === 4) return $p;
            return Carbon::createFromFormat('Y-m', $p)->format('M Y');
        })->values()->all();

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    private function getRecentActivity(): array
    {
        $recentPersons = Person::with(['user', 'affiliations.organization'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($person) {
                $org = $person->affiliations->first()?->organization;
                return [
                    'type' => 'person_registered',
                    'title' => $person->given_name . ' ' . $person->family_name,
                    'subtitle' => $org ? ($org->display_name ?? $org->legal_name) : 'No organization',
                    'time' => $person->created_at,
                    'time_ago' => $person->created_at->diffForHumans(),
                ];
            });

        $recentProjectHeads = User::role('Project Head')
            ->with('person')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'project_head_appointed',
                    'title' => $user->name,
                    'subtitle' => 'Appointed as Project Head',
                    'time' => $user->updated_at,
                    'time_ago' => $user->updated_at->diffForHumans(),
                ];
            });

        return $recentPersons
            ->concat($recentProjectHeads)
            ->sortByDesc('time')
            ->take(15)
            ->values()
            ->toArray();
    }

    private function getPersonsByDepartment($departmentId): array
    {
        return Person::with(['user', 'affiliations.organization'])
            ->whereHas('affiliations', fn($q) => $q->where('department_id', $departmentId)->where('status', 'active'))
            ->orderBy('family_name')
            ->limit(100)
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->given_name . ' ' . $person->family_name,
                    'email' => $person->user?->email,
                    'phone' => $person->phone_number,
                    'gender' => $person->gender,
                    'organization' => $person->affiliations->first()?->organization?->legal_name,
                ];
            })
            ->toArray();
    }

    private function getPersonsByOrganization($organizationId): array
    {
        return Person::with(['user', 'affiliations'])
            ->whereHas('affiliations', fn($q) => $q->where('organization_id', $organizationId))
            ->orderBy('family_name')
            ->limit(100)
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->given_name . ' ' . $person->family_name,
                    'email' => $person->user?->email,
                    'phone' => $person->phone_number,
                    'gender' => $person->gender,
                    'created_at' => $person->created_at->format('M d, Y'),
                ];
            })
            ->toArray();
    }

    private function getNewPersons(): array
    {
        $query = Person::with(['user', 'affiliations.organization'])
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->selectedDepartmentId) {
            $query->whereHas('affiliations', fn($q) => $q->where('department_id', $this->selectedDepartmentId));
        } elseif ($this->selectedOrganizationId) {
            $query->whereHas('affiliations', fn($q) => $q->where('organization_id', $this->selectedOrganizationId));
        }

        return $query->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->given_name . ' ' . $person->family_name,
                    'email' => $person->user?->email,
                    'phone' => $person->phone_number,
                    'organization' => $person->affiliations->first()?->organization?->legal_name,
                    'created_at' => $person->created_at->format('M d, Y'),
                ];
            })
            ->toArray();
    }

    public function exportDashboard()
    {
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Export feature coming soon'
        ]);
    }
}
