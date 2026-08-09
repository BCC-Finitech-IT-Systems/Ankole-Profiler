<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workplans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            // Denormalized from department->organization_id at creation, kept
            // immutable, so scoping queries match the Policy Repository's
            // convention of filtering on organization_id directly.
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('title')->nullable();
            $table->enum('status', [
                'draft', 'submitted', 'approved', 'in_progress', 'completed', 'deferred', 'cancelled',
            ])->default('draft');
            $table->text('review_comment')->nullable();
            $table->text('decision_comment')->nullable();

            $table->foreignId('supersedes_workplan_id')->nullable()->constrained('workplans')->nullOnDelete();
            $table->foreignId('carried_forward_from_id')->nullable()->constrained('workplans')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['department_id', 'year', 'version_number']);
            $table->index(['department_id', 'status']);
            $table->index('organization_id');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workplans');
    }
};
