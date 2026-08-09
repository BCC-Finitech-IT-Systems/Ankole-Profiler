<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->date('reported_on');
            $table->unsignedTinyInteger('percent_complete');
            $table->text('notes')->nullable();
            $table->text('blockers')->nullable();
            $table->text('next_steps')->nullable();
            $table->unsignedInteger('time_spent_minutes')->nullable();
            $table->date('revised_due_date')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assignment_id', 'reported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_progress_updates');
    }
};
