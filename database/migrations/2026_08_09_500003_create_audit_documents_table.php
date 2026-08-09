<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_report_id')->constrained('audit_reports')->cascadeOnDelete();
            $table->enum('document_type', [
                'report', 'management_letter', 'management_response', 'evidence', 'other',
            ]);
            $table->unsignedInteger('version_number');
            $table->boolean('is_current')->default(true);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('hash', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['audit_report_id', 'document_type', 'version_number'], 'audit_documents_report_type_version_unique');
            $table->index(['audit_report_id', 'document_type', 'is_current'], 'audit_documents_report_type_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_documents');
    }
};
