<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_person_roles', function (Blueprint $table) {
            // Nullable while the legacy free-form 'role' string is migrated;
            // MySQL allows multiple NULLs in the unique index, so untyped
            // legacy rows are unaffected during the transition.
            $table->foreignId('role_type_id')->nullable()->after('role')
                ->constrained('role_types')->nullOnDelete();

            $table->index(['unit_id', 'role_type_id']);
            $table->unique(['unit_id', 'person_id', 'role_type_id'], 'unit_person_role_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('unit_person_roles', function (Blueprint $table) {
            $table->dropUnique('unit_person_role_type_unique');
            $table->dropIndex(['unit_id', 'role_type_id']);
            $table->dropConstrainedForeignId('role_type_id');
        });
    }
};
