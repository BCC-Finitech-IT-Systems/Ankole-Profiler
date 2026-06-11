<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_grants', function (Blueprint $table) {
            $table->id();
            // Polymorphic: works for PersonAffiliation, ProjectAffiliation, UnitPersonRole
            $table->morphs('grantable');
            // The specific permission being granted
            $table->enum('permission', ['view', 'edit', 'approve', 'export', 'manage']);
            // Optional: which resource scope this grant covers
            $table->string('scope')->nullable(); // e.g. 'persons', 'reports', 'projects'
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // grantable_type + grantable_id index is created automatically by morphs()
            $table->index(['permission', 'grantable_type']);
            $table->index(['granted_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_grants');
    }
};
