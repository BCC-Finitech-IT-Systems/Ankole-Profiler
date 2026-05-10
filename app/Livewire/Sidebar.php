<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public array $menuItems = [];
    public string $searchTerm = '';
    public array $expandedSections = [];
    public bool $mobileDrawerOpen = false;

    public function mount(): void
    {
        $this->loadMenuItems();
        $this->initializeExpandedSections();
    }

    /**
     * Initialize expanded sections based on active route
     */
    private function initializeExpandedSections(): void
    {
        $this->expandedSections = [];

        foreach ($this->menuItems as $sectionKey => $section) {
            // Check if section itself is marked active
            if (isset($section['active']) && $section['active']) {
                $this->expandedSections[$sectionKey] = true;
                continue;
            }

            // Check if any item in the section is active
            if (isset($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $item) {
                    if (isset($item['active']) && $item['active']) {
                        $this->expandedSections[$sectionKey] = true;
                        break;
                    }
                }
            }
        }
    }

    public function toggleSection(string $sectionKey): void
    {
        if (isset($this->expandedSections[$sectionKey])) {
            unset($this->expandedSections[$sectionKey]);
        } else {
            $this->expandedSections[$sectionKey] = true;
        }
    }

    public function toggleMobileDrawer(): void
    {
        $this->mobileDrawerOpen = !$this->mobileDrawerOpen;
        $this->dispatch('sidebar-toggled', open: $this->mobileDrawerOpen);
    }

    public function closeMobileDrawer(): void
    {
        $this->mobileDrawerOpen = false;
        $this->dispatch('sidebar-toggled', open: false);
    }

    public function openMobileDrawer(): void
    {
        $this->mobileDrawerOpen = true;
        $this->dispatch('sidebar-toggled', open: true);
    }

    /**
     * Filter menu items based on search term
     */
    public function updatedSearchTerm(): void
    {
        if (empty($this->searchTerm)) {
            $this->loadMenuItems();
            return;
        }

        $searchLower = strtolower($this->searchTerm);
        $filteredItems = [];

        foreach ($this->menuItems as $sectionKey => $section) {
            $matchingItems = [];

            if (isset($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $item) {
                    if (str_contains(strtolower($item['label']), $searchLower)) {
                        $matchingItems[] = $item;
                    }
                }
            }

            if (!empty($matchingItems)) {
                $filteredItems[$sectionKey] = array_merge($section, ['items' => $matchingItems]);
                $this->expandedSections[$sectionKey] = true;
            }
        }

        if (!empty($filteredItems)) {
            $this->menuItems = $filteredItems;
        }
    }

    public function loadMenuItems(): void
    {
        $user = Auth::user();

        if (!$user) {
            $this->menuItems = $this->getDefaultMenu();
            return;
        }

        // Get menu based on user role
        $roleMenuMap = [
            'Super Admin' => 'getSuperAdminMenu',
            'Organization Admin' => 'getOrganizationAdminMenu',
            'Department Manager' => 'getDepartmentManagerMenu',
            'Data Entry Clerk' => 'getDataEntryClerkMenu',
            'Compliance Officer' => 'getComplianceOfficerMenu',
            'Read Only' => 'getReadOnlyMenu',
            'Person' => 'getPersonMenu',
        ];

        $menuLoaded = false;
        if (method_exists($user, 'hasRole')) {
            foreach ($roleMenuMap as $role => $method) {
                if ($user->hasRole($role)) {
                    $this->menuItems = $this->$method();
                    $menuLoaded = true;
                    break;
                }
            }
        }

        if (!$menuLoaded) {
            $this->menuItems = $this->getDefaultMenu();
        }

        // Add Project Head menu items if applicable
        if (method_exists($user, 'hasRole') && $user->hasRole('Project Head')) {
            $this->menuItems = array_merge($this->menuItems, $this->getProjectHeadMenu());
        }
    }

    public function getPersonMenu(): array
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications->count() : 0;

        return [
            'person-actions' => [
                'title' => 'My Dashboard',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'items' => [
                    [
                        'label' => 'My Profile',
                        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'route' => 'persons.profile-current',
                        'permission' => 'view-persons',
                        'active' => request()->routeIs('persons.profile-current'),
                    ],
                    [
                        'label' => 'My Projects',
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h1m4 0h1M9 16h1',
                        'route' => 'dashboard',
                        'permission' => 'view-org-persons',
                    ],
                    [
                        'label' => 'Privacy Settings',
                        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                        'route' => 'dashboard',
                        'permission' => 'edit-persons',
                    ],
                    [
                        'label' => 'Notifications',
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'route' => 'person.notifications',
                        'permission' => 'view-persons',
                        'badge' => $unreadCount,
                        'active' => request()->routeIs('person.notifications'),
                    ],
                    [
                        'label' => 'My Documents',
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        'route' => 'dashboard',
                        'permission' => 'view-persons-document',
                    ],
                    [
                        'label' => 'Family Connections',
                        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                        'route' => 'dashboard',
                        'permission' => 'view-persons',
                    ],
                    [
                        'label' => 'Help & Support',
                        'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        'route' => 'dashboard',
                        'permission' => 'Support-persons',
                    ],
                ]
            ]
        ];
    }

    public function getProjectHeadMenu(): array
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications->count() : 0;
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'person-actions' => [
                'title' => 'My Dashboard',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'active' => in_array($activeRoute, ['persons.profile-current', 'person.notifications']),
                'items' => [
                    [
                        'label' => 'My Profile',
                        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'route' => 'persons.profile-current',
                        'permission' => 'view-persons',
                        'active' => $activeRoute === 'persons.profile-current',
                    ],
                    [
                        'label' => 'My Projects',
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h1m4 0h1M9 16h1',
                        'route' => 'dashboard',
                        'permission' => 'view-org-persons',
                    ],
                    [
                        'label' => 'Privacy Settings',
                        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                        'route' => 'dashboard',
                        'permission' => 'edit-persons',
                    ],
                    [
                        'label' => 'Notifications',
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'route' => 'person.notifications',
                        'permission' => 'view-persons',
                        'badge' => $unreadCount,
                        'active' => $activeRoute === 'person.notifications',
                    ],
                ]
            ],
            'person_registry' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import', 'persons.export']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export'],
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ]
        ];
    }

    private function getSuperAdminMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => in_array($activeRoute, ['dashboard', 'departments.dashboard']),
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                    ['label' => 'My Organization', 'route' => 'dashboard', 'permission' => 'view-org-analytics'],
                    ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z', 'active' => $activeRoute === 'departments.dashboard'],
                ],
            ],
            'organization' => [
                'title' => 'Projects Mgt',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'active' => in_array($activeRoute, ['organizations.index', 'organizations.create', 'organizations.import', 'organization-units.index', 'organization-units.create', 'organization-units.applications', 'departments.index']),
                'items' => [
                    ['label' => 'All Projects', 'route' => 'organizations.index', 'permission' => 'view-Organizations', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5', 'active' => $activeRoute === 'organizations.index'],
                    ['label' => 'Add New Project', 'route' => 'organizations.create', 'permission' => 'create-Organizations', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'active' => $activeRoute === 'organizations.create'],
                    ['label' => 'Import Projects', 'route' => 'organizations.import', 'permission' => 'import-Organizations', 'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10', 'active' => $activeRoute === 'organizations.import'],
                    ['label' => 'Project Units', 'route' => 'organization-units.index', 'permission' => 'view-units', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'active' => $activeRoute === 'organization-units.index'],
                    ['label' => 'Create Unit', 'route' => 'organization-units.create', 'permission' => 'create-units', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'active' => $activeRoute === 'organization-units.create'],
                    ['label' => 'Unit Applications', 'route' => 'organization-units.applications', 'permission' => 'review-organization-units', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'active' => $activeRoute === 'organization-units.applications'],
                    ['label' => 'Departments', 'route' => 'departments.index', 'permission' => 'view-departments', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'active' => $activeRoute === 'departments.index'],
                ]
            ],
            'person_registry' => [
                'title' => 'Person Registry',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import', 'persons.export']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export'],
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.filter-profiles', 'communication.history']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Filter Profiles', 'route' => 'communication.filter-profiles', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.filter-profiles'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ],
            'administration' => [
                'title' => 'Administration',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'active' => in_array($activeRoute, ['admin.users.index', 'admin.roles.index', 'admin.permissions.index', 'admin.allowEmailDomains']),
                'items' => [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'permission' => 'manage-users', 'active' => $activeRoute === 'admin.users.index'],
                    ['label' => 'Roles', 'route' => 'admin.roles.index', 'permission' => 'manage-roles', 'active' => $activeRoute === 'admin.roles.index'],
                    ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'permission' => 'manage-permissions', 'active' => $activeRoute === 'admin.permissions.index'],
                    ['label' => 'Allow Email Domains', 'route' => 'admin.allowEmailDomains', 'permission' => 'manage-allowEmailDomains', 'active' => $activeRoute === 'admin.allowEmailDomains'],
                ]
            ]
        ];
    }

    private function getOrganizationAdminMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'Dashboard Overview', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                ],
            ],
            'organization' => [
                'title' => 'My Projects',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'active' => in_array($activeRoute, ['organizations.current-project', 'departments.index', 'departments.dashboard']),
                'items' => [
                    ['label' => 'Projects Profile', 'route' => 'organizations.current-project', 'permission' => 'view-own-Organization', 'active' => $activeRoute === 'organizations.current-project'],
                    ['label' => 'Departments', 'route' => 'departments.index', 'permission' => 'view-departments', 'active' => $activeRoute === 'departments.index'],
                    ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'active' => $activeRoute === 'departments.dashboard'],
                ]
            ],
            'person_registry' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import', 'persons.export']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export'],
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.filter-profiles', 'communication.history']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Filter Profiles', 'route' => 'communication.filter-profiles', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.filter-profiles'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ]
        ];
    }

    private function getDepartmentManagerMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'My Department Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                ],
            ],
            'team' => [
                'title' => 'My Team',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'items' => [
                    ['label' => 'Team Members', 'route' => 'dashboard', 'permission' => 'view-dept-team'],
                    ['label' => 'Add Team Member', 'route' => 'dashboard', 'permission' => 'manage-dept-team'],
                ]
            ],
            'records' => [
                'title' => 'Department Records',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'active' => in_array($activeRoute, ['departments.index', 'departments.dashboard']),
                'items' => [
                    ['label' => 'Department List', 'route' => 'departments.index', 'permission' => 'view-departments', 'active' => $activeRoute === 'departments.index'],
                    ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'active' => $activeRoute === 'departments.dashboard'],
                    ['label' => 'Staff in My Department', 'route' => 'dashboard', 'permission' => 'view-dept-staff'],
                    ['label' => 'Students in My Class', 'route' => 'dashboard', 'permission' => 'view-dept-students'],
                    ['label' => 'Patients in My Ward', 'route' => 'dashboard', 'permission' => 'view-dept-patients'],
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ]
        ];
    }

    private function getDataEntryClerkMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'My Work Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                ]
            ],
            'person_entry' => [
                'title' => 'Person Entry',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.search']),
                'items' => [
                    ['label' => 'Add New Person', 'route' => 'dashboard', 'permission' => 'create-persons'],
                    ['label' => 'Edit Person', 'route' => 'dashboard', 'permission' => 'edit-persons'],
                    ['label' => 'Search Persons', 'route' => 'persons.search', 'permission' => 'view-persons', 'active' => $activeRoute === 'persons.search'],
                ]
            ],
            'my_work' => [
                'title' => 'My Work',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'items' => [
                    ['label' => 'Pending Tasks', 'route' => 'dashboard', 'permission' => 'view-tasks', 'badge' => $this->getPendingTasksCount()],
                    ['label' => 'Recent Entries', 'route' => 'dashboard', 'permission' => 'view-own-entries'],
                    ['label' => 'Data Quality Issues', 'route' => 'dashboard', 'permission' => 'view-quality-issues'],
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ]
        ];
    }

    private function getComplianceOfficerMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'Compliance Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                    ['label' => 'Risk Overview', 'route' => 'dashboard', 'permission' => 'view-risk-overview'],
                ]
            ],
            'compliance' => [
                'title' => 'Compliance Management',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'items' => [
                    ['label' => 'Consent Management', 'route' => 'dashboard', 'permission' => 'manage-consents'],
                    ['label' => 'Pending Consents', 'route' => 'dashboard', 'permission' => 'manage-consents', 'badge' => $this->getPendingConsentsCount()],
                    ['label' => 'Audit Logs', 'route' => 'dashboard', 'permission' => 'view-audit-logs'],
                    ['label' => 'KYC Management', 'route' => 'dashboard', 'permission' => 'manage-kyc'],
                    ['label' => 'Data Subject Rights', 'route' => 'dashboard', 'permission' => 'manage-data-rights'],
                ]
            ],
            'reports' => [
                'title' => 'Compliance Reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'items' => [
                    ['label' => 'GDPR Compliance Report', 'route' => 'dashboard', 'permission' => 'view-compliance-reports'],
                    ['label' => 'Consent Statistics', 'route' => 'dashboard', 'permission' => 'view-compliance-reports'],
                    ['label' => 'Risk Assessment Report', 'route' => 'dashboard', 'permission' => 'view-compliance-reports'],
                ]
            ],
            'communication' => [
                'title' => 'Communication Monitoring',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                    ['label' => 'Communication Analytics', 'route' => 'communication.index', 'permission' => 'view-communication-analytics', 'active' => $activeRoute === 'communication.index'],
                ]
            ]
        ];
    }

    private function getReadOnlyMenu(): array
    {
        $activeRoute = request()->route()?->getName() ?? '';

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'Dashboard View', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                ]
            ],
            'persons' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.search']),
                'items' => [
                    ['label' => 'View Persons', 'route' => 'persons.all', 'permission' => 'view-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Search Persons', 'route' => 'persons.search', 'permission' => 'view-persons', 'active' => $activeRoute === 'persons.search'],
                ]
            ],
            'reports' => [
                'title' => 'Reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'items' => [
                    ['label' => 'View Reports', 'route' => 'dashboard', 'permission' => 'view-reports'],
                    ['label' => 'Export Reports', 'route' => 'dashboard', 'permission' => 'export-reports'],
                ]
            ]
        ];
    }

    private function getDefaultMenu(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard'],
                ]
            ]
        ];
    }

    // Helper methods for badge counts
    private function getPendingVerificationCount(): int
    {
        // return Person::where('verification_status', 'pending')->count();
        return 23;
    }

    private function getExpiredAffiliationsCount(): int
    {
        // return PersonAffiliation::where('end_date', '<', now())->count();
        return 5;
    }

    private function getPendingConsentsCount(): int
    {
        // return Consent::where('status', 'pending')->count();
        return 12;
    }

    private function getFailedSyncsCount(): int
    {
        // return SyncLog::where('status', 'failed')->count();
        return 2;
    }

    private function getPendingTasksCount(): int
    {
        // return Task::where('assigned_to', auth()->id())->where('status', 'pending')->count();
        return 8;
    }

    public function render()
    {
        return view('livewire.sidebar');
    }

    public function getUserOrganizations()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return \App\Models\PersonAffiliation::where('user_id', $user->id)
            ->with('organization')
            ->get()
            ->map(fn($affiliation) => $affiliation->organization);
    }
}
