<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_types', function (Blueprint $table) {
            // Add department_id column if it doesn't exist
            if (!Schema::hasColumn('role_types', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('departments')
                    ->nullOnDelete();
            }

            // Optionally: If you want to migrate data from organization_id to department_id
            // You can keep organization_id for now and handle the migration separately
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_types', function (Blueprint $table) {
            if (Schema::hasColumn('role_types', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }
};
