<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_type_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_type_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('role_type_id')->references('id')->on('role_types')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');

            // Unique constraint to prevent duplicate assignments
            $table->unique(['role_type_id', 'permission_id']);

            // Indexes
            $table->index('role_type_id');
            $table->index('permission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_type_permissions');
    }
};
