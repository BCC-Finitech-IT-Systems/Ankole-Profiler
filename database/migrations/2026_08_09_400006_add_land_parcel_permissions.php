<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * land registration permissions without a full reseed. Mirrors the
     * seeder in database/seeders/RolesAndPermissionsSeeder.php — keep both
     * in sync.
     */
    public function up(): void
    {
        $permissions = [
            'view-land-parcels',
            'create-land-parcels',
            'edit-land-parcels',
            'archive-land-parcels',
            'upload-land-documents',
            'download-land-documents',
            'manage-land-disputes',
            'view-land-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'Super Admin' => $permissions,
            'Organization Admin' => $permissions,
            'Department Manager' => [
                'view-land-parcels', 'create-land-parcels', 'edit-land-parcels',
                'upload-land-documents', 'download-land-documents', 'view-land-dashboard',
            ],
            'Compliance Officer' => ['view-land-parcels', 'download-land-documents', 'view-land-dashboard'],
            'Staff' => ['view-land-parcels', 'download-land-documents'],
            'Read Only' => ['view-land-parcels', 'view-land-dashboard'],
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
            'view-land-parcels',
            'create-land-parcels',
            'edit-land-parcels',
            'archive-land-parcels',
            'upload-land-documents',
            'download-land-documents',
            'manage-land-disputes',
            'view-land-dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
