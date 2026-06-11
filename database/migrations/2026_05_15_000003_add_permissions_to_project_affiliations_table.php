<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_affiliations', function (Blueprint $table) {
            // Granular permissions at project level (mirrors PersonAffiliation)
            $table->json('permissions')->nullable()->after('status');

            // Direct unit link for unit-level project members
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->after('permissions')
                ->constrained('organization_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_affiliations', function (Blueprint $table) {
            $table->dropForeign(['organization_unit_id']);
            $table->dropColumn(['permissions', 'organization_unit_id']);
        });
    }
};
