<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class ProjectHeadRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Project Head permissions
        $permissions = [
            // Person management within project
            'view project persons' => 'View all persons associated with the project',
            'create project persons' => 'Create new persons under the project',
            'edit project persons' => 'Edit existing persons under the project',
            'delete project persons' => 'Delete persons from the project',

            // Project data management
            'view project details' => 'View project/organization details',
            'edit project details' => 'Edit project/organization details',

            // Project reports and modules
            'view project reports' => 'View project reports and analytics',
            'export project data' => 'Export project data to various formats',
            'access project modules' => 'Access project-specific modules',

            // Project-specific activities
            'manage project activities' => 'Manage activities within the project',
            'view project dashboard' => 'View the project dashboard',

        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
            $this->command->info("Permission created/exists: {$name}");
        }

        // Create Project Head role if it doesn't exist
        $projectHeadRole = Role::firstOrCreate(
            ['name' => 'Project Head', 'guard_name' => 'web'],
            ['description' => 'Head of a project/organization with management capabilities']
        );

        // Assign permissions to Project Head role
        $projectHeadRole->syncPermissions(array_keys($permissions));

        // Adding permissions from getProjectHeadMenu to the main permissions array
        $permissions = array_merge($permissions, $this->getProjectHeadMenu()['permissions']);

        $projectHeadRole->syncPermissions(array_keys($permissions));

        $this->command->info('Project Head role created with ' . count($permissions) . ' permissions.');

        Log::info('ProjectHeadRoleSeeder completed', [
            'role' => 'Project Head',
            'permissions_count' => count($permissions),
        ]);
    }

    // Updating permissions to match refined list
    public function getProjectHeadMenu()
    {
        return [
            'permissions' => [
                'view-persons',
                'edit-persons',
                'delete-persons',
                'create-org-persons',
                'import-org-persons',
                'export-org-persons',

                'send-communications',
                'view-communications',
                'manage-roles',
                'view-reports',
                'edit-project-details',
            ],
            'menu_items' => [
                'dashboard',
                'persons',
                'roles',
                'reports',
                'settings',
            ],
        ];
    }
}
