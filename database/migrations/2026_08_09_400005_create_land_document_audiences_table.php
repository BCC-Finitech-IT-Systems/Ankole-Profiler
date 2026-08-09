<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_document_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_document_id')->constrained('land_documents')->cascadeOnDelete();
            // Exactly one of the three is set per row (enforced in the model).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->string('role_name')->nullable();
            $table->timestamps();

            $table->index('land_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_document_audiences');
    }
};
