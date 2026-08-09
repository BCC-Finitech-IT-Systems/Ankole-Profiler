<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_report_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_report_id')->constrained('audit_reports')->cascadeOnDelete();
            // Exactly one of the four is set per row (enforced in the model).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->string('role_name')->nullable();
            $table->foreignId('person_id')->nullable()->constrained('persons')->cascadeOnDelete();
            $table->timestamps();

            $table->index('audit_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_report_audiences');
    }
};
