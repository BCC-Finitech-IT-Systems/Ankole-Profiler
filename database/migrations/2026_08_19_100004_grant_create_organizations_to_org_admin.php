<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Idempotent grant migration, mirrors database/seeders/RolesAndPermissionsSeeder.php
     * — keep both in sync. The "Add Organization" button on the organizations
     * index page gated on 'create-units' (wrong permission, for org units not
     * organizations) and was hardcoded disabled besides — Organization Admin
     * never actually had a working way to add institutions under their
     * diocese.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'create-organizations', 'guard_name' => 'web']);

        $orgAdmin = Role::where('name', 'Organization Admin')->first();
        if ($orgAdmin) {
            $orgAdmin->givePermissionTo('create-organizations');
        }
    }

    public function down(): void
    {
        $orgAdmin = Role::where('name', 'Organization Admin')->first();
        if ($orgAdmin) {
            $orgAdmin->revokePermissionTo('create-organizations');
        }
    }
};
