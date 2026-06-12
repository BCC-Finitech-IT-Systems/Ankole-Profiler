<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Role types without a department: when every unit role referencing
        // the type sits in exactly one department, the scope is unambiguous
        // and can be recorded. Types used across departments (or not used by
        // units at all, e.g. seeded global codes like STAFF/MEMBER) stay
        // global on purpose.
        $candidates = DB::table('role_types')->whereNull('department_id')->pluck('id');

        foreach ($candidates as $roleTypeId) {
            $departmentIds = DB::table('unit_person_roles')
                ->where('role_type_id', $roleTypeId)
                ->join('organization_units', 'organization_units.id', '=', 'unit_person_roles.unit_id')
                ->whereNotNull('organization_units.department_id')
                ->distinct()
                ->pluck('organization_units.department_id');

            if ($departmentIds->count() === 1) {
                DB::table('role_types')
                    ->where('id', $roleTypeId)
                    ->update(['department_id' => $departmentIds->first()]);
            }
        }
    }

    public function down(): void
    {
        // Inferred scopes cannot be distinguished from manually set ones;
        // leaving them in place is safe.
    }
};
