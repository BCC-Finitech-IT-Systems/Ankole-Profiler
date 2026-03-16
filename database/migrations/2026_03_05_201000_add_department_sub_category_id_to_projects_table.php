<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('department_sub_category_id')
                ->nullable()
                ->after('department_id')
                ->constrained('department_sub_categories')
                ->nullOnDelete();

            $table->index(['department_id', 'department_sub_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['department_id', 'department_sub_category_id']);
            $table->dropConstrainedForeignId('department_sub_category_id');
        });
    }
};
