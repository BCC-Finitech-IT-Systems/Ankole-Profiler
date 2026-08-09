<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            // A person can be both 'support' and 'watcher' on the same
            // assignment (two rows) — deliberately not one-role-per-person.
            $table->enum('role', ['support', 'watcher']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assignment_id', 'person_id', 'role']);
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_people');
    }
};
