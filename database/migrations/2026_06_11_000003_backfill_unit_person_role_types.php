<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $pairs = DB::table('unit_person_roles')
            ->whereNull('role_type_id')
            ->join('organization_units', 'organization_units.id', '=', 'unit_person_roles.unit_id')
            ->select('unit_person_roles.unit_id', 'unit_person_roles.role', 'organization_units.department_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            if ($pair->department_id === null) {
                // The unit itself is missing a department; this is a data
                // cleanup task, not something a backfill can decide.
                Log::warning('unit_person_roles backfill skipped: unit has no department', [
                    'unit_id' => $pair->unit_id,
                    'role' => $pair->role,
                ]);
                continue;
            }

            $code = strtoupper($pair->role);

            $roleTypeId = DB::table('role_types')
                ->where('code', $code)
                ->where('department_id', $pair->department_id)
                ->value('id');

            // Fall back to a global (department-less) code before creating one.
            $roleTypeId ??= DB::table('role_types')
                ->where('code', $code)
                ->whereNull('department_id')
                ->value('id');

            // Codes are globally unique, so a code claimed by another
            // department must be reused rather than duplicated; flag it for
            // manual review.
            if ($roleTypeId === null) {
                $roleTypeId = DB::table('role_types')->where('code', $code)->value('id');
                if ($roleTypeId !== null) {
                    Log::warning('unit_person_roles backfill reused role type from another department', [
                        'unit_id' => $pair->unit_id,
                        'role' => $pair->role,
                        'role_type_id' => $roleTypeId,
                    ]);
                }
            }

            $roleTypeId ??= DB::table('role_types')->insertGetId([
                'department_id' => $pair->department_id,
                'code' => $code,
                'name' => ucfirst(strtolower($pair->role)),
                'description' => 'Created by unit role backfill',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('unit_person_roles')
                ->where('unit_id', $pair->unit_id)
                ->where('role', $pair->role)
                ->whereNull('role_type_id')
                ->update(['role_type_id' => $roleTypeId]);
        }
    }

    public function down(): void
    {
        DB::table('unit_person_roles')->update(['role_type_id' => null]);
    }
};
