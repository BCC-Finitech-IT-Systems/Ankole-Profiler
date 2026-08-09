<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * assignment permissions without a full reseed. Mirrors the seeder in
     * database/seeders/RolesAndPermissionsSeeder.php — keep both in sync.
     */
    public function up(): void
    {
        $permissions = [
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'close-assignments',
            'report-assignment-progress',
            'review-assignments',
            'upload-assignment-documents',
            'download-assignment-documents',
            'view-assignment-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'Super Admin' => $permissions,
            'Organization Admin' => $permissions,
            'Department Manager' => [
                'view-assignments', 'create-assignments', 'edit-assignments', 'close-assignments',
                'report-assignment-progress', 'upload-assignment-documents',
                'download-assignment-documents', 'view-assignment-dashboard',
            ],
            'Compliance Officer' => ['view-assignments', 'download-assignment-documents', 'view-assignment-dashboard'],
            'Staff' => ['view-assignments', 'download-assignment-documents'],
            'Read Only' => ['view-assignments', 'view-assignment-dashboard'],
            'Person' => ['view-assignments', 'report-assignment-progress', 'download-assignment-documents'],
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
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'close-assignments',
            'report-assignment-progress',
            'review-assignments',
            'upload-assignment-documents',
            'download-assignment-documents',
            'view-assignment-dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
