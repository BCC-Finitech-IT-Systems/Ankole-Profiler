<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Sidebar extends Component
{
    public $menuItems = [];
    public $searchTerm = '';
    public $expandedSections = [];

    public function mount()
    {
        $this->loadMenuItems();

        // Reset expanded sections and only expand the section containing the active route
        $this->expandedSections = [];

        foreach ($this->menuItems as $sectionKey => $section) {
            if (isset($section['active']) && $section['active']) {
                $this->expandedSections = [$sectionKey => true];
                break;
            }

            // Check if any item in the section is active
            foreach ($section['items'] as $item) {
                if (isset($item['active']) && $item['active']) {
                    $this->expandedSections = [$sectionKey => true];
                    break 2;
                }
            }
        }
    }

    public function toggleSection($sectionKey)
    {
        if (isset($this->expandedSections[$sectionKey])) {
            unset($this->expandedSections[$sectionKey]);
        } else {
            $this->expandedSections[$sectionKey] = true;
        }
    }

    public function loadMenuItems()
    {
        $user = Auth::user();

        if (!$user) {
            $this->menuItems = $this->getDefaultMenu();
            return;
        }

        // Get menu based on user role
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            $this->menuItems = $this->getSuperAdminMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Organization Admin')) {
            $this->menuItems = $this->getOrganizationAdminMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Department Manager')) {
            $this->menuItems = $this->getDepartmentManagerMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Data Entry Clerk')) {
            $this->menuItems = $this->getDataEntryClerkMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Compliance Officer')) {
            $this->menuItems = $this->getComplianceOfficerMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Read Only')) {
            $this->menuItems = $this->getReadOnlyMenu();
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('Person')) {
            $this->menuItems = $this->getPersonMenu();
        } else {
            $this->menuItems = $this->getDefaultMenu();
        }

        // Ensure Project Head menu items are included
        if (method_exists($user, 'hasRole') && $user->hasRole('Project Head')) {
            $this->menuItems = array_merge($this->menuItems, $this->getProjectHeadMenu());
        }
    }

    public function getPersonMenu()
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
                        'permission' => 'view-persons'
                    ],

                    [
                        'label' => 'My Projects',
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h1m4 0h1M9 16h1',
                        'route' => 'dashboard',
                        'permission' => 'view-org-persons'
                    ],
                    [
                        'label' => 'Privacy Settings',
                        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                        'route' => 'dashboard',
                        'permission' => 'edit-persons'
                    ],
                    [
                        'label' => 'Notifications',
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'route' => 'person.notifications',
                        'permission' => 'view-persons',
                        'badge' => $unreadCount
                    ],
                    [
                        'label' => 'My Documents',
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        'route' => 'dashboard',
                        'permission' => 'view-persons-document'
                    ],
                    [
                        'label' => 'Family Connections',
                        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                        'route' => 'dashboard',
                        'permission' => 'view-persons'
                    ],
                    [
                        'label' => 'Help & Support',
                        'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        'route' => 'dashboard',
                        'permission' => 'Support-persons'
                    ],
                ]
            ]

        ];
    }
    public function getProjectHeadMenu()
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications->count() : 0;
        $activeRoute = request()->route()->getName();

        return [
            'person-actions' => [
                'title' => 'My Dashboard',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'open' => !in_array($activeRoute, ['persons.profile-current', 'dashboard', 'person.notifications']),
                'items' => [
                    [
                        'label' => 'My Profile',
                        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        'route' => 'persons.profile-current',
                        'permission' => 'view-persons'
                    ],
                    [
                        'label' => 'My Projects',
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 4h1m4 0h1M9 16h1',
                        'route' => 'dashboard',
                        'permission' => 'view-org-persons'
                    ],
                    [
                        'label' => 'Privacy Settings',
                        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                        'route' => 'dashboard',
                        'permission' => 'edit-persons'
                    ],
                    [
                        'label' => 'Notifications',
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'route' => 'person.notifications',
                        'permission' => 'view-persons',
                        'badge' => $unreadCount
                    ],
                ]
            ],
            'person_registry' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export']
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history']
                ]
            ]
        ];
    }

    private function getSuperAdminMenu()
    {
        $activeRoute = request()->route()->getName();

        $dashboardItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard' && !request()->has('department_id')],
            ['label' => 'My Organization', 'route' => 'dashboard', 'permission' => 'view-org-analytics', 'active' => $activeRoute === 'dashboard' && !request()->has('department_id')],
            ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z']
        ];

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => $activeRoute === 'dashboard',
                'items' => $dashboardItems,
            ],
            'organization' => [
                'title' => 'Projects Mgt',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'active' => in_array(request()->route()->getName(), ['dashboard', 'departments.index']),
                'items' => [
                    ['label' => 'All Projects', 'route' => 'organizations.index', 'permission' => 'view-Organizations', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
                    ['label' => 'Add New Project', 'route' => 'organizations.create', 'permission' => 'create-Organizations', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                    ['label' => 'Import Projects', 'route' => 'organizations.import', 'permission' => 'import-Organizations', 'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10'],
                    // ['label' => 'Hierarchy', 'route' => 'dashboard', 'permission' => 'view-Organizations-hierarchy', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z'],
                    // ['label' => 'Sites & Locations', 'route' => 'dashboard', 'permission' => 'view-sites', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['label' => 'Project Units', 'route' => 'organization-units.index', 'permission' => 'view-units', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    ['label' => 'Create Unit', 'route' => 'organization-units.create', 'permission' => 'create-units', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                    ['label' => 'Unit Applications', 'route' => 'organization-units.applications', 'permission' => 'review-organization-units', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['label' => 'Departments', 'route' => 'departments.index', 'permission' => 'view-departments', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'active' => request()->route()->getName() === 'departments.index'],

                ]
            ],
            'person_registry' => [
                'title' => 'Person Registry',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'active' => in_array(request()->route()->getName(), ['persons.all', 'persons.create', 'persons.import']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export']
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Filter Profiles', 'route' => 'communication.filter-profiles', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.filter-profiles'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history']
                ]
            ],
            'administration' => [
                'title' => 'Administration',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['admin.users.index', 'admin.roles.index', 'admin.permissions.index', 'allowEmailDomains']),
                'items' => [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'permission' => 'manage-users', 'active' => $activeRoute === 'users.index'],
                    ['label' => 'Roles', 'route' => 'admin.roles.index', 'permission' => 'manage-roles', 'active' => $activeRoute === 'roles.index'],
                    ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'permission' => 'manage-permissions', 'active' => $activeRoute === 'permissions.index'],
                    ['label' => 'Allow Email Domains', 'route' => 'admin.allowEmailDomains', 'permission' => 'manage-allowEmailDomains', 'active' => $activeRoute === 'allowEmailDomains']
                ]
            ]
        ];
    }

    private function getOrganizationAdminMenu()
    {
        $activeRoute = request()->route()->getName();

        $dashboardItems = [
            ['label' => 'Dashboard Overview', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard' && !request()->has('department_id')],
            // ['label' => 'My Organization Analytics', 'route' => 'dashboard', 'permission' => 'view-org-analytics', 'active' => $activeRoute === 'dashboard' && !request()->has('department_id')],
        ];

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => $activeRoute === 'dashboard',
                'items' => $dashboardItems,
            ],
            'organization' => [
                'title' => 'My Projects',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'active' => in_array($activeRoute, ['organizations.current-project', 'organization-units.index', 'organization-units.create', 'organization-units.applications', 'departments.index', 'departments.dashboard']),
                'items' => [
                    ['label' => 'Projects Profile', 'route' => 'organizations.current-project', 'permission' => 'view-own-Organization', 'active' => $activeRoute === 'organizations.current-project'],
                    // ['label' => 'Project Units', 'route' => 'organization-units.index', 'permission' => 'view-own-units', 'active' => $activeRoute === 'organization-units.index'],
                    // // ['label' => 'Create Unit', 'route' => 'organization-units.create', 'permission' => 'create-units', 'active' => $activeRoute === 'organization-units.create'],
                    // ['label' => 'Unit Applications', 'route' => 'organization-units.applications', 'permission' => 'review-organization-units', 'active' => $activeRoute === 'organization-units.applications'],
                    ['label' => 'Departments', 'route' => 'departments.index', 'permission' => 'view-departments', 'active' => $activeRoute === 'departments.index'],
                    ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'active' => $activeRoute === 'departments.dashboard']
                ]
            ],
            'person_registry' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import']),
                'items' => [
                    ['label' => 'All Persons', 'route' => 'persons.all', 'permission' => 'view-org-persons', 'active' => $activeRoute === 'persons.all'],
                    ['label' => 'Add New Person', 'route' => 'persons.create', 'permission' => 'create-org-persons', 'active' => $activeRoute === 'persons.create'],
                    ['label' => 'Import Persons', 'route' => 'persons.import', 'permission' => 'import-org-persons', 'active' => $activeRoute === 'persons.import'],
                    ['label' => 'Export Persons', 'route' => 'persons.export', 'permission' => 'export-org-persons', 'active' => $activeRoute === 'persons.export']
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Filter Profiles', 'route' => 'communication.filter-profiles', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.filter-profiles'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                ]
            ]
        ];
    }

    private function getDepartmentManagerMenu()
    {
        $activeRoute = request()->route()->getName();

        $dashboardItems = [
            ['label' => 'My Department Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard' && !request()->has('department_id')],
        ];

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => $activeRoute === 'dashboard',
                'items' => $dashboardItems,
            ],
            'team' => [
                'title' => 'My Team',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['dashboard']),
                'items' => [
                    ['label' => 'Team Members', 'route' => 'dashboard', 'permission' => 'view-dept-team'],
                    ['label' => 'Add Team Member', 'route' => 'dashboard', 'permission' => 'manage-dept-team']
                ]
            ],
            'records' => [
                'title' => 'Department Records',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'active' => in_array($activeRoute, ['dashboard', 'departments.index']),
                'items' => [
                    ['label' => 'Department List', 'route' => 'departments.index', 'permission' => 'view-departments', 'active' => $activeRoute === 'departments.index'],
                    ['label' => 'Departments Dashboard', 'route' => 'departments.dashboard', 'permission' => 'view-departments-dashboard', 'active' => $activeRoute === 'departments.dashboard'],
                    ['label' => 'Staff in My Department', 'route' => 'dashboard', 'permission' => 'view-dept-staff'],
                    ['label' => 'Students in My Class', 'route' => 'dashboard', 'permission' => 'view-dept-students'],
                    ['label' => 'Patients in My Ward', 'route' => 'dashboard', 'permission' => 'view-dept-patients']
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history']
                ]
            ]
        ];
    }

    private function getDataEntryClerkMenu()
    {
        $activeRoute = request()->route()->getName();

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'My Work Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard']
                ]
            ],
            'person_entry' => [
                'title' => 'Person Entry',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array($activeRoute, ['persons.all', 'persons.create', 'persons.import']),
                'items' => [
                    ['label' => 'Add New Person', 'route' => 'dashboard', 'permission' => 'create-persons'],
                    ['label' => 'Edit Person', 'route' => 'dashboard', 'permission' => 'edit-persons'],
                    ['label' => 'Search Persons', 'route' => 'persons.search', 'permission' => 'view-persons']
                ]
            ],
            'my_work' => [
                'title' => 'My Work',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'active' => in_array($activeRoute, ['dashboard']),
                'items' => [
                    ['label' => 'Pending Tasks', 'route' => 'dashboard', 'permission' => 'view-tasks', 'badge' => $this->getPendingTasksCount()],
                    ['label' => 'Recent Entries', 'route' => 'dashboard', 'permission' => 'view-own-entries'],
                    ['label' => 'Data Quality Issues', 'route' => 'dashboard', 'permission' => 'view-quality-issues']
                ]
            ],
            'communication' => [
                'title' => 'Communication',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Send Message', 'route' => 'communication.send', 'permission' => 'send-communications', 'active' => $activeRoute === 'communication.send'],
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history']
                ]
            ]
        ];
    }

    private function getComplianceOfficerMenu()
    {
        $activeRoute = request()->route()->getName();

        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => $activeRoute === 'dashboard',
                'items' => [
                    ['label' => 'Compliance Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard', 'active' => $activeRoute === 'dashboard'],
                    ['label' => 'Risk Overview', 'route' => 'dashboard', 'permission' => 'view-risk-overview']
                ]
            ],
            'compliance' => [
                'title' => 'Compliance Management',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'active' => in_array($activeRoute, ['dashboard']),
                'items' => [
                    ['label' => 'Consent Management', 'route' => 'dashboard', 'permission' => 'manage-consents'],
                    ['label' => 'Pending Consents', 'route' => 'dashboard', 'permission' => 'manage-consents', 'badge' => $this->getPendingConsentsCount()],
                    ['label' => 'Audit Logs', 'route' => 'dashboard', 'permission' => 'view-audit-logs'],
                    ['label' => 'KYC Management', 'route' => 'dashboard', 'permission' => 'manage-kyc'],
                    ['label' => 'Data Subject Rights', 'route' => 'dashboard', 'permission' => 'manage-data-rights']
                ]
            ],
            'reports' => [
                'title' => 'Compliance Reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'active' => in_array($activeRoute, ['dashboard']),
                'items' => [
                    ['label' => 'GDPR Compliance Report', 'route' => 'dashboard', 'permission' => 'view-compliance-reports'],
                    ['label' => 'Consent Statistics', 'route' => 'dashboard', 'permission' => 'view-compliance-reports'],
                    ['label' => 'Risk Assessment Report', 'route' => 'dashboard', 'permission' => 'view-compliance-reports']
                ]
            ],
            'communication' => [
                'title' => 'Communication Monitoring',
                'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                'active' => in_array($activeRoute, ['communication.send', 'communication.history', 'communication.index']),
                'items' => [
                    ['label' => 'Message History', 'route' => 'communication.history', 'permission' => 'view-communications', 'active' => $activeRoute === 'communication.history'],
                    ['label' => 'Communication Analytics', 'route' => 'communication.index', 'permission' => 'view-communication-analytics', 'active' => $activeRoute === 'communication.index']
                ]
            ]
        ];
    }

    private function getReadOnlyMenu()
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'active' => request()->route()->getName() === 'dashboard',
                'items' => [
                    ['label' => 'Dashboard View', 'route' => 'dashboard', 'permission' => 'view-dashboard']
                ]
            ],
            'persons' => [
                'title' => 'Person Mgt',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'active' => in_array(request()->route()->getName(), ['persons.all', 'persons.create', 'persons.import']),
                'items' => [
                    ['label' => 'View Persons', 'route' => 'persons.all', 'permission' => 'view-persons'],
                    ['label' => 'Search Persons', 'route' => 'persons.search', 'permission' => 'view-persons']
                ]
            ],
            'reports' => [
                'title' => 'Reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'active' => in_array(request()->route()->getName(), ['dashboard']),
                'items' => [
                    ['label' => 'View Reports', 'route' => 'dashboard', 'permission' => 'view-reports'],
                    ['label' => 'Export Reports', 'route' => 'dashboard', 'permission' => 'export-reports']
                ]
            ]
        ];
    }

    private function getDefaultMenu()
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'route' => 'dashboard',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'view-dashboard']
                ]
            ]
        ];
    }

    // Helper methods for badge counts
    private function getPendingVerificationCount()
    {
        // This would query the database for pending verifications
        // return Person::where('verification_status', 'pending')->count();
        return 23;
    }

    private function getExpiredAffiliationsCount()
    {
        // This would query the database for expired affiliations
        // return PersonAffiliation::where('end_date', '<', now())->count();
        return 5;
    }

    private function getPendingConsentsCount()
    {
        // This would query the database for pending consents
        // return Consent::where('status', 'pending')->count();
        return 12;
    }

    private function getFailedSyncsCount()
    {
        // This would query the database for failed syncs
        // return SyncLog::where('status', 'failed')->count();
        return 2;
    }

    private function getPendingTasksCount()
    {
        // This would query the database for pending tasks for current user
        // return Task::where('assigned_to', auth()->id())->where('status', 'pending')->count();
        return 8;
    }

    /**
     * Helper method to check if route exists and return fallback route if not
     */
    private function getRoute($routeName, $fallback = 'dashboard')
    {
        try {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                return $routeName;
            }
        } catch (\Exception $e) {
            // Route doesn't exist
        }

        return $fallback;
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

        // Fetch organizations associated with the current user
        return \App\Models\PersonAffiliation::where('user_id', $user->id)
            ->with('organization')
            ->get()
            ->map(function ($affiliation) {
                return $affiliation->organization;
            });
    }
}
