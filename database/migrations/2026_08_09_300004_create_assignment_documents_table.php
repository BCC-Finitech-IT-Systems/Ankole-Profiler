<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // Assignment (supporting) | AssignmentProgressUpdate (evidence)
            $table->enum('kind', ['supporting', 'evidence']);
            $table->string('title')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('hash', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id', 'kind'], 'assignment_documents_documentable_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_documents');
    }
};
