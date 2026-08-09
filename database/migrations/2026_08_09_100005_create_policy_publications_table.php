<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_version_id')->constrained('policy_versions')->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete(); // institution
            $table->enum('status', [
                'not_sent', 'sent', 'acknowledged', 'adopted',
                'partially_adopted', 'exception_requested', 'overdue',
            ])->default('not_sent');
            $table->date('due_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('adoption_date')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->text('implementation_notes')->nullable();
            $table->text('exception_reason')->nullable();
            $table->enum('exception_status', ['none', 'requested', 'approved', 'rejected'])->default('none');
            $table->foreignId('exception_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('exception_decided_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['policy_version_id', 'organization_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['policy_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_publications');
    }
};
