<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workplan_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplan_activity_id')->constrained('workplan_activities')->cascadeOnDelete();
            $table->date('reported_on');
            $table->unsignedTinyInteger('percent_complete');
            $table->text('work_completed')->nullable();
            $table->text('pending_work')->nullable();
            $table->text('challenges')->nullable();
            $table->text('corrective_action')->nullable();
            $table->decimal('expenditure', 14, 2)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workplan_activity_id', 'reported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workplan_progress_updates');
    }
};
