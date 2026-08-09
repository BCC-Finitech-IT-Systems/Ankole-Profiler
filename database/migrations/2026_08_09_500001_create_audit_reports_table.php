<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('audited_institution_name')->nullable();
            $table->string('title');
            $table->enum('audit_type', [
                'internal', 'external', 'financial', 'compliance', 'operational', 'institutional',
            ]);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('issuing_body');
            $table->date('issue_date');
            $table->enum('status', ['draft', 'issued', 'under_review', 'closed'])->default('draft');
            $table->string('overall_rating')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('responsible_follow_up_owner_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->boolean('restricted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index('department_id');
            $table->index('audit_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
