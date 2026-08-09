<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workplan_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplan_id')->constrained('workplans')->cascadeOnDelete();
            $table->string('strategic_objective');
            $table->text('activity');
            $table->text('expected_output')->nullable();
            $table->string('performance_indicator')->nullable();
            $table->string('baseline')->nullable();
            $table->string('target')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->decimal('budget_estimate', 14, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->string('responsible_team')->nullable();
            $table->text('dependencies')->nullable();
            $table->enum('status', [
                'not_started', 'in_progress', 'completed', 'deferred', 'cancelled',
            ])->default('not_started');
            $table->unsignedTinyInteger('percent_complete')->default(0);
            $table->foreignId('carried_forward_from_activity_id')->nullable()
                ->constrained('workplan_activities')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workplan_id', 'status']);
            $table->index('end_date');
            $table->index('responsible_person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workplan_activities');
    }
};
