<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('reference_code', 50);
            $table->string('title');
            $table->string('policy_category', 100);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            // FK to policy_versions added in a follow-up migration to avoid a
            // circular dependency between policies and policy_versions.
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference_code']);
            $table->index(['organization_id', 'status']);
            $table->index('department_id');
            $table->index('policy_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
