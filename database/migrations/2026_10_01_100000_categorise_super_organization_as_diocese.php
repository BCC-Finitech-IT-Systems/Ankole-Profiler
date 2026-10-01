<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Installs seeded before DepartmentSeeder set category 'diocese' on the
 * diocese row left it as 'other', so every "the diocese" lookup (policy
 * pages, the organization parent picker, DioceseManager) found nothing.
 * Give the super organization that category when no diocese exists yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('organizations')->where('category', 'diocese')->whereNull('deleted_at')->exists()) {
            return;
        }

        DB::table('organizations')
            ->where('is_super', true)
            ->whereNull('deleted_at')
            ->update(['category' => 'diocese']);
    }

    public function down(): void
    {
        // Not reversible: the previous category isn't recorded, and 'diocese'
        // is what DepartmentSeeder gives this row on fresh installs.
    }
};
