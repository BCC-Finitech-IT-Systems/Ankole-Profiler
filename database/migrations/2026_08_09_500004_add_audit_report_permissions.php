<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * audit report register permissions without a full reseed. Mirrors the
     * seeder in database/seeders/RolesAndPermissionsSeeder.php — keep both
     * in sync.
     */
    public function up(): void
    {
        $permissions = [
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'archive-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'Super Admin' => $permissions,
            'Organization Admin' => $permissions,
            'Department Manager' => [
                'view-audit-reports', 'create-audit-reports', 'edit-audit-reports',
                'download-audit-documents', 'view-audit-dashboard',
            ],
            'Compliance Officer' => [
                'view-audit-reports', 'create-audit-reports', 'edit-audit-reports',
                'download-audit-documents', 'view-audit-dashboard',
            ],
            'Staff' => ['view-audit-reports', 'download-audit-documents'],
            'Read Only' => ['view-audit-reports', 'view-audit-dashboard'],
        ];

        foreach ($grants as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            'view-audit-reports',
            'create-audit-reports',
            'edit-audit-reports',
            'archive-audit-reports',
            'download-audit-documents',
            'view-audit-dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
