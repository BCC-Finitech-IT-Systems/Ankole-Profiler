<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_units', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index(['department_id', 'is_active']);
        });

        // Back-fill from the legacy reverse bridge on departments
        // departments.legacy_organization_unit_id → organization_units.department_id
        \DB::statement('
            UPDATE organization_units ou
            JOIN departments d ON d.legacy_organization_unit_id = ou.id
            SET ou.department_id = d.id
            WHERE ou.department_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('organization_units', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id', 'is_active']);
            $table->dropColumn('department_id');
        });
    }
};
