<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('version_label', 20)->nullable();
            $table->text('summary')->nullable();
            $table->enum('status', [
                'draft', 'pending_approval', 'approved', 'published', 'superseded', 'archived',
            ])->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->date('adoption_due_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->date('synod_approval_date')->nullable();
            $table->string('synod_approval_reference')->nullable();
            $table->enum('visibility', ['diocese_wide', 'restricted'])->default('diocese_wide');

            // The approved policy document itself. Kept as columns (not a row
            // in policy_documents) so "document required before publish" is a
            // trivial required-column check.
            $table->string('document_path')->nullable();
            $table->string('document_original_name')->nullable();
            $table->string('document_mime')->nullable();
            $table->unsignedBigInteger('document_size')->nullable();
            $table->char('document_hash', 64)->nullable();

            $table->foreignId('supersedes_version_id')->nullable()
                ->constrained('policy_versions')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'version_number']);
            $table->index('status');
            $table->index(['policy_id', 'status']);
            $table->index('adoption_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
