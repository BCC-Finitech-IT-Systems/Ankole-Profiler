<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_person_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('organization_units')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('role'); // 'leader' | 'secretary' | 'treasurer' | 'member' | custom
            // Granular permissions at this role within this unit
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'person_id', 'role'], 'unit_person_role_unique');
            $table->index(['unit_id', 'person_id']);
            $table->index(['person_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_person_roles');
    }
};
