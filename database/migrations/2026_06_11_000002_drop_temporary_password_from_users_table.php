<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The column stored temporary passwords in plain text. Nothing reads it
     * (the welcome-email flow uses an encrypted cache entry), so drop it and
     * the credentials it holds.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'temporary_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('temporary_password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('temporary_password')->nullable();
        });
    }
};
