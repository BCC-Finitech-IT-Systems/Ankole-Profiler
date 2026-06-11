<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->after('department_id')
                ->constrained('organization_units')
                ->nullOnDelete();

            $table->index(['organization_unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('person_affiliations', function (Blueprint $table) {
            $table->dropForeign(['organization_unit_id']);
            $table->dropIndex(['organization_unit_id', 'status']);
            $table->dropColumn('organization_unit_id');
        });
    }
};
