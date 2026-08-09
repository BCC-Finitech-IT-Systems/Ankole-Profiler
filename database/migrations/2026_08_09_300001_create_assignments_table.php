<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            // Linked context (Department|Project|WorkplanActivity|Organization); null for freestanding assignments.
            $table->nullableMorphs('assignable');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', [
                'not_started', 'in_progress', 'blocked', 'awaiting_review', 'completed', 'deferred', 'cancelled',
            ])->default('not_started');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('revised_due_date')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('dependencies')->nullable();
            $table->unsignedTinyInteger('percent_complete')->default(0);
            $table->foreignId('responsible_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->text('review_comment')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index('department_id');
            $table->index('due_date');
            $table->index('responsible_person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
