<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generic, immutable, polymorphic audit trail. Wired only from the
        // policy repository feature for now; the shape is deliberately
        // generic so other modules that need an immutable audit trail
        // (assignment tracking, land registration, audit report register)
        // can write to it later without a new migration.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->morphs('auditable');
            $table->string('event', 80);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['organization_id', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
