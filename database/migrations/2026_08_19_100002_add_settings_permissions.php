<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration so existing deployments pick up the new
     * settings-management permission without a full reseed. Mirrors the
     * seeder in database/seeders/RolesAndPermissionsSeeder.php — keep both
     * in sync.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo('manage-settings');
        }
    }

    public function down(): void
    {
        Permission::where('name', 'manage-settings')->delete();
    }
};
