<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->enum('document_type', [
                'purchase_agreement', 'survey_report', 'deed_plan', 'application',
                'receipt', 'correspondence', 'court_document', 'title_copy', 'other',
            ]);
            $table->unsignedInteger('version_number');
            $table->boolean('is_current')->default(true);
            $table->boolean('restricted')->default(false);
            $table->string('custody_location')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('hash', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['land_parcel_id', 'document_type', 'version_number'], 'land_documents_parcel_type_version_unique');
            $table->index(['land_parcel_id', 'document_type', 'is_current'], 'land_documents_parcel_type_current_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_documents');
    }
};
