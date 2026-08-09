<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * policy-repository permissions without a full reseed. Mirrors the
     * seeder in database/seeders/RolesAndPermissionsSeeder.php — keep both
     * in sync.
     */
    public function up(): void
    {
        $permissions = [
            'view-policies',
            'create-policies',
            'edit-policies',
            'archive-policies',
            'approve-policies',
            'publish-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'update-policy-adoption',
            'decide-policy-exceptions',
            'view-policy-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'Super Admin' => $permissions,
            'Organization Admin' => $permissions,
            'Department Manager' => [
                'view-policies', 'create-policies', 'edit-policies',
                'download-policy-documents', 'view-policy-adoption', 'view-policy-dashboard',
            ],
            'Compliance Officer' => [
                'view-policies', 'download-policy-documents',
                'view-policy-adoption', 'view-policy-dashboard',
            ],
            'Staff' => ['view-policies', 'download-policy-documents'],
            'Read Only' => ['view-policies', 'view-policy-adoption'],
            'Person' => ['view-policies'],
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
            'view-policies',
            'create-policies',
            'edit-policies',
            'archive-policies',
            'approve-policies',
            'publish-policies',
            'download-policy-documents',
            'view-policy-adoption',
            'update-policy-adoption',
            'decide-policy-exceptions',
            'view-policy-dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
