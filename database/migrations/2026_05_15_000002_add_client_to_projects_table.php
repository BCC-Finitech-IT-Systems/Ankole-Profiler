<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Internal client: a Person already in the system
            $table->foreignId('client_person_id')
                ->nullable()
                ->after('admin_user_id')
                ->constrained('persons')
                ->nullOnDelete();

            // External client name when no Person record exists
            $table->string('external_client_name')->nullable()->after('client_person_id');

            // Organization unit the project belongs to (for unit-level projects)
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->after('department_id')
                ->constrained('organization_units')
                ->nullOnDelete();

            $table->index(['client_person_id']);
            $table->index(['organization_unit_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_person_id']);
            $table->dropForeign(['organization_unit_id']);
            $table->dropColumn(['client_person_id', 'external_client_name', 'organization_unit_id']);
        });
    }
};
