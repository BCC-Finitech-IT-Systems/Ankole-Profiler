<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * workplan permissions without a full reseed. Mirrors the seeder in
     * database/seeders/RolesAndPermissionsSeeder.php — keep both in sync.
     */
    public function up(): void
    {
        $permissions = [
            'view-workplans',
            'create-workplans',
            'edit-workplans',
            'submit-workplans',
            'approve-workplans',
            'archive-workplans',
            'record-workplan-progress',
            'carry-forward-workplans',
            'upload-workplan-documents',
            'download-workplan-documents',
            'view-workplan-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $grants = [
            'Super Admin' => $permissions,
            'Organization Admin' => $permissions,
            'Department Manager' => [
                'view-workplans', 'create-workplans', 'edit-workplans', 'submit-workplans',
                'record-workplan-progress', 'carry-forward-workplans',
                'upload-workplan-documents', 'download-workplan-documents', 'view-workplan-dashboard',
            ],
            'Compliance Officer' => [
                'view-workplans', 'download-workplan-documents', 'view-workplan-dashboard',
            ],
            'Staff' => ['view-workplans', 'download-workplan-documents'],
            'Read Only' => ['view-workplans'],
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
            'view-workplans',
            'create-workplans',
            'edit-workplans',
            'submit-workplans',
            'approve-workplans',
            'archive-workplans',
            'record-workplan-progress',
            'carry-forward-workplans',
            'upload-workplan-documents',
            'download-workplan-documents',
            'view-workplan-dashboard',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
