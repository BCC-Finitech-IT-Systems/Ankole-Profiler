<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sub_category')->nullable()->after('name');
            $table->index(['department_id', 'sub_category']);
        });

        Schema::create('project_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_departments');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['department_id', 'sub_category']);
            $table->dropColumn('sub_category');
        });
    }
};
