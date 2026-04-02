<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Project Head permissions
        $permissions = [
            // Person management within project
            'view project persons',
            'create project persons',
            'edit project persons',
            'delete project persons',

            // Project data management
            'view project details',
            'edit project details',

            // Project reports and modules
            'view project reports',
            'export project data',
            'access project modules',

            // Project-specific activities
            'manage project activities',
            'view project dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Create Project Head role if it doesn't exist
        $projectHeadRole = Role::firstOrCreate(
            ['name' => 'Project Head', 'guard_name' => 'web']
        );

        // Assign permissions to Project Head role
        $projectHeadRole->syncPermissions($permissions);

        // Log the creation
        \Illuminate\Support\Facades\Log::info('Project Head role and permissions created/updated', [
            'role' => 'Project Head',
            'permissions' => $permissions,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Project Head role
        $role = Role::where('name', 'Project Head')->first();
        if ($role) {
            $role->delete();
        }

        // Remove Project Head specific permissions
        $permissions = [
            'view project persons',
            'create project persons',
            'edit project persons',
            'delete project persons',
            'view project details',
            'edit project details',
            'view project reports',
            'export project data',
            'access project modules',
            'manage project activities',
            'view project dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
