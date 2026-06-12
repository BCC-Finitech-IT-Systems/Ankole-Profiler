<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Person;
use App\Models\Organization;
use App\Models\PersonAffiliation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardComponent extends Component
{
    public $stats = [];
    public $recentActivities = [];
    public $alerts = [];
    public $currentOrganization;
    public $isSuperAdmin = false;
    public $membershipPending = false;
    public $alertsAcknowledged = false;
    public $lastRefreshedAt;

    public function mount()
    {
        $this->initializeUserContext();
        Log::info('Current Organization:', ['organization' => $this->currentOrganization]);
        $this->loadDashboardData();
    }

    private function initializeUserContext()
    {
        $user = Auth::user();

        if ($user) {
            // Check if user is Super Admin
            if (method_exists($user, 'hasRole')) {
                $this->isSuperAdmin = $user->hasRole('Super Admin');
            }

            // Self-registered users hold no roles until a diocese admin
            // approves their pending application.
            $personId = $user->personId();
            if ($personId && $user->roles->isEmpty()) {
                $this->membershipPending = PersonAffiliation::where('person_id', $personId)
                    ->where('status', 'pending')
                    ->exists();
            }
        }

        // Get current organization
        $this->currentOrganization = user_current_organization();
    }

    public function loadDashboardData()
    {
        try {
            // Cache dashboard stats for 5 minutes
            $cacheKey = $this->dashboardCacheKey();

            $this->stats = Cache::remember($cacheKey, 300, function () {
                return $this->calculateStats();
            });

            $this->recentActivities = $this->getRecentActivities();
            $this->alerts = $this->alertsAcknowledged ? [] : $this->getSystemAlerts();
            $this->lastRefreshedAt = now()->format('M j, Y g:i A');
        } catch (\Exception $e) {
            Log::error('Dashboard data loading error: ' . $e->getMessage());

            // Set default empty stats on error
            $this->stats = [
                'total_persons' => 0,
                'persons_today' => 0,
                'total_organizations' => 0,
                'new_organizations' => 0,
                'active_affiliations' => 0,
                'expired_affiliations' => 0,
                'pending_memberships' => 0,
                'pending_verifications' => 0,
                'pending_consents' => 0,
                'system_health' => 100,
            ];

            $this->recentActivities = [];
            $this->alerts = [];
            $this->lastRefreshedAt = now()->format('M j, Y g:i A');
        }
    }

    private function dashboardCacheKey(): string
    {
        $user = Auth::user();
        $roleScope = $this->isSuperAdmin ? 'super-admin' : 'org-user';
        $orgScope = $this->isSuperAdmin
            ? 'all'
            : ($this->currentOrganization?->id ?? 'none');

        return "dashboard_stats_{$roleScope}_{$orgScope}_user_" . ($user?->id ?? 'guest');
    }

    private function calculateStats()
    {
        $stats = [];

        try {
            if ($this->isSuperAdmin) {
                // Super Admin sees all data
                $stats['total_persons'] = Person::count();
                $stats['persons_today'] = Person::whereDate('created_at', today())->count();
                $stats['total_organizations'] = Organization::where('is_active', true)->count();
                $stats['new_organizations'] = Organization::where('is_active', true)
                    ->whereDate('created_at', '>=', now()->subDays(30))
                    ->count();
                $stats['active_affiliations'] = PersonAffiliation::where('status', 'active')->count();
                $stats['expired_affiliations'] = PersonAffiliation::where('status', 'inactive')
                    ->whereNotNull('end_date')
                    ->where('end_date', '<', now())
                    ->count();
            } else {
                // Organization Admin sees only their organization data
                if ($this->currentOrganization) {
                    $stats['total_persons'] = PersonAffiliation::where('organization_id', $this->currentOrganization->id)
                        ->distinct('person_id')
                        ->count('person_id');

                    $stats['persons_today'] = PersonAffiliation::where('organization_id', $this->currentOrganization->id)
                        ->whereDate('person_affiliations.created_at', today())
                        ->distinct('person_id')
                        ->count('person_id');

                    $stats['total_organizations'] = 1; // Only their organization
                    $stats['new_organizations'] = 0;

                    $stats['active_affiliations'] = PersonAffiliation::where('organization_id', $this->currentOrganization->id)
                        ->where('status', 'active')
                        ->count();

                    $stats['expired_affiliations'] = PersonAffiliation::where('organization_id', $this->currentOrganization->id)
                        ->where('status', 'inactive')
                        ->whereNotNull('end_date')
                        ->where('end_date', '<', now())
                        ->count();
                } else {
                    // No organization context
                    $stats = [
                        'total_persons' => 0,
                        'persons_today' => 0,
                        'total_organizations' => 0,
                        'new_organizations' => 0,
                        'active_affiliations' => 0,
                        'expired_affiliations' => 0,
                    ];
                }
            }

            // Common stats for all users
            $stats['pending_memberships'] = $this->getPendingMemberships();
            $stats['pending_verifications'] = $this->getPendingVerifications();
            $stats['pending_consents'] = $this->getPendingConsents();
            $stats['system_health'] = $this->calculateSystemHealth($stats);

        } catch (\Exception $e) {
            Log::error('Stats calculation error: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    private function getPendingMemberships(): int
    {
        try {
            $query = PersonAffiliation::where('status', 'pending');

            if (!$this->isSuperAdmin && $this->currentOrganization) {
                $query->where('organization_id', $this->currentOrganization->id);
            }

            return $query->count();
        } catch (\Exception $e) {
            Log::error('Pending memberships calculation error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getPendingVerifications()
    {
        try {
            // Count persons with incomplete profiles (missing critical information)
            $query = Person::where(function($q) {
                // Missing phone OR email OR national ID OR date of birth
                $q->whereDoesntHave('phones')
                  ->orWhereDoesntHave('emailAddresses')
                  ->orWhereDoesntHave('identifiers')
                  ->orWhereNull('date_of_birth');
            });

            if (!$this->isSuperAdmin && $this->currentOrganization) {
                $query->whereHas('affiliations', function($q) {
                    $q->where('organization_id', $this->currentOrganization->id);
                });
            }

            return $query->count();
        } catch (\Exception $e) {
            Log::error('Pending verifications calculation error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getPendingConsents()
    {
        try {
            // Check if ConsentRecord model exists
            if (class_exists(\App\Models\ConsentRecord::class)) {
                $query = \App\Models\ConsentRecord::where('status', 'pending')
                    ->orWhere(function($q) {
                        $q->where('status', 'active')
                          ->where('expires_at', '<', now()->addDays(30));
                    });

                if (!$this->isSuperAdmin && $this->currentOrganization) {
                    $query->whereHas('person.affiliations', function($q) {
                        $q->where('organization_id', $this->currentOrganization->id);
                    });
                }

                return $query->count();
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('Pending consents calculation error: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateSystemHealth($stats)
    {
        try {
            // Calculate system health based on various factors
            $healthScore = 100;

            // Check database connections
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $healthScore -= 30;
            }

            // Check for data quality issues
            $missingDataQuery = Person::where(function ($query) {
                $query->whereNull('given_name')
                    ->orWhereNull('family_name');
            });

            if (!$this->isSuperAdmin && $this->currentOrganization) {
                $missingDataQuery->whereHas('affiliations', function ($query) {
                    $query->where('organization_id', $this->currentOrganization->id);
                });
            }

            $missingData = $missingDataQuery->count();

            if ($missingData > 0) {
                $healthScore -= min(20, $missingData * 2);
            }

            // Check for expired affiliations
            $expiredAffiliations = isset($stats['expired_affiliations']) ? $stats['expired_affiliations'] : 0;
            if ($expiredAffiliations > 10) {
                $healthScore -= 10;
            }

            // Check for pending verifications
            $pendingVerifications = ($stats['pending_verifications'] ?? 0) + ($stats['pending_memberships'] ?? 0);
            if ($pendingVerifications > 50) {
                $healthScore -= 15;
            }

            return max(0, min(100, $healthScore));
        } catch (\Exception $e) {
            Log::error('System health calculation error: ' . $e->getMessage());
            return 100;
        }
    }

    private function getRecentActivities()
    {
        try {
            $activities = [];

            // Get recent person registrations
            $recentPersons = Person::with('affiliations.Organization')
                ->latest()
                ->limit(3);

            if (!$this->isSuperAdmin && $this->currentOrganization) {
                $recentPersons->whereHas('affiliations', function($q) {
                    $q->where('organization_id', $this->currentOrganization->id);
                });
            }

            foreach ($recentPersons->get() as $person) {
                $affiliation = $person->affiliations->first();
                $orgName = 'Unknown Organization';

                if ($affiliation && $affiliation->Organization) {
                    $orgName = $affiliation->Organization->display_name
                        ? $affiliation->Organization->display_name
                        : $affiliation->Organization->legal_name;
                }

                $activities[] = [
                    'type' => 'person',
                    'title' => 'New person "' . $person->full_name . '" registered',
                    'description' => 'Complete profile with organizational affiliation to ' . $orgName,
                    'time' => $person->created_at->diffForHumans(),
                    'timestamp' => $person->created_at?->timestamp ?? 0,
                    'badge' => 'Person',
                    'badge_color' => 'success',
                    'icon' => 'user-group',
                    'url' => route('persons.show', $person->id),
                ];
            }

            // Get recent organization updates (Super Admin only)
            if ($this->isSuperAdmin) {
                $recentOrgs = Organization::latest('updated_at')->limit(2)->get();

                foreach ($recentOrgs as $org) {
                    $orgDisplayName = $org->display_name ? $org->display_name : $org->legal_name;

                    $activities[] = [
                        'type' => 'organization',
                        'title' => 'Organization "' . $orgDisplayName . '" updated',
                        'description' => 'Organization information modified',
                        'time' => $org->updated_at->diffForHumans(),
                        'timestamp' => $org->updated_at?->timestamp ?? 0,
                        'badge' => 'Organization',
                        'badge_color' => 'info',
                        'icon' => 'building',
                        'url' => route('organizations.show', $org->id),
                    ];
                }
            }

            // Get recent affiliations
            $recentAffiliations = PersonAffiliation::with(['person', 'Organization'])
                ->where('status', 'active')
                ->latest()
                ->limit(3);

            if (!$this->isSuperAdmin && $this->currentOrganization) {
                $recentAffiliations->where('organization_id', $this->currentOrganization->id);
            }

            foreach ($recentAffiliations->get() as $affiliation) {
                $orgDisplayName = $affiliation->Organization
                    ? ($affiliation->Organization->display_name ?: $affiliation->Organization->legal_name)
                    : 'Unknown Organization';
                $personName = $affiliation->person?->full_name ?? 'Unknown person';

                $activities[] = [
                    'type' => 'affiliation',
                    'title' => 'New affiliation verified',
                    'description' => $personName . ' affiliated with ' . $orgDisplayName,
                    'time' => $affiliation->created_at->diffForHumans(),
                    'timestamp' => $affiliation->created_at?->timestamp ?? 0,
                    'badge' => 'Affiliation',
                    'badge_color' => 'secondary',
                    'icon' => 'link',
                    'url' => $affiliation->person_id ? route('persons.show', $affiliation->person_id) : route('persons.all'),
                ];
            }

            // Sort by time (most recent first)
            usort($activities, function($a, $b) {
                return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
            });

            return array_slice($activities, 0, 8);
        } catch (\Exception $e) {
            Log::error('Recent activities loading error: ' . $e->getMessage());
            return [];
        }
    }

    private function getSystemAlerts()
    {
        try {
            $alerts = [];

            // Critical: Data Compliance
            $pendingConsents = isset($this->stats['pending_consents']) ? $this->stats['pending_consents'] : 0;
            if ($pendingConsents > 0) {
                $alerts[] = [
                    'level' => 'error',
                    'title' => 'Critical: Data Compliance Issue',
                    'description' => $pendingConsents . ' person records lack proper consent documentation. Immediate action required.',
                    'priority' => 'High Priority',
                    'icon' => 'exclamation-circle',
                    'url' => route('persons.all'),
                ];
            }

            $pendingMemberships = isset($this->stats['pending_memberships']) ? $this->stats['pending_memberships'] : 0;
            if ($pendingMemberships > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'Pending Membership Applications',
                    'description' => $pendingMemberships . ' membership applications are waiting for review.',
                    'priority' => 'High Priority',
                    'icon' => 'exclamation-triangle',
                    'url' => route('organizations.membership-applications'),
                ];
            }

            // Warning: Pending Verifications
            $pendingVerifications = isset($this->stats['pending_verifications']) ? $this->stats['pending_verifications'] : 0;
            if ($pendingVerifications > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'Pending Verifications',
                    'description' => $pendingVerifications . ' person profiles require identity verification and document upload.',
                    'priority' => 'Medium Priority',
                    'icon' => 'exclamation-triangle',
                    'url' => route('persons.all'),
                ];
            }

            // Info: Expired Affiliations
            $expiredAffiliations = isset($this->stats['expired_affiliations']) ? $this->stats['expired_affiliations'] : 0;
            if ($expiredAffiliations > 0) {
                $alerts[] = [
                    'level' => 'info',
                    'title' => 'Expired Affiliations',
                    'description' => $expiredAffiliations . ' affiliations have expired and may need renewal or archival.',
                    'priority' => 'Low Priority',
                    'icon' => 'information-circle',
                    'url' => route('persons.all'),
                ];
            }

            // Success: System Health
            $systemHealth = isset($this->stats['system_health']) ? $this->stats['system_health'] : 0;
            if ($systemHealth > 95) {
                $alerts[] = [
                    'level' => 'success',
                    'title' => 'System Operating Optimally',
                    'description' => 'All person registry data is healthy with no critical issues detected.',
                    'priority' => 'Information',
                    'icon' => 'check-circle',
                    'url' => route('dashboard'),
                ];
            } elseif ($systemHealth < 75) {
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'System Health Degraded',
                    'description' => 'System health is below optimal levels. Review data quality and pending actions.',
                    'priority' => 'Medium Priority',
                    'icon' => 'exclamation-triangle',
                    'url' => route('persons.all'),
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            Log::error('System alerts loading error: ' . $e->getMessage());
            return [];
        }
    }

    public function refreshData()
    {
        try {
            // Clear cache
            Cache::forget($this->dashboardCacheKey());
            $this->alertsAcknowledged = false;

            // Reload data
            $this->loadDashboardData();

            // Dispatch browser event for notification
            $this->dispatch('dashboard-refreshed', [
                'message' => 'Dashboard data refreshed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard refresh error: ' . $e->getMessage());

            $this->dispatch('dashboard-refresh-failed', [
                'message' => 'Failed to refresh dashboard data'
            ]);
        }
    }

    public function markAlertsRead()
    {
        $this->alertsAcknowledged = true;
        $this->alerts = [];

        $this->dispatch('dashboard-alerts-read', [
            'message' => 'Dashboard alerts marked as read'
        ]);
    }

    public function viewAllActivity()
    {
        return redirect()->route('persons.all');
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-component', [
            'stats' => $this->stats,
            'recentActivities' => $this->recentActivities,
            'alerts' => $this->alerts,
        ])->layout('layouts.app', [
            'title' => 'Dashboard - Alpha',
            'pageTitle' => 'Profiler'
        ]);
    }
}
