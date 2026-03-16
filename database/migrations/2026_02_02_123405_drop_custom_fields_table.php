<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            if (Schema::hasColumn('custom_field_values', 'custom_field_id')) {
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'custom_field_values' AND COLUMN_NAME = 'custom_field_id'");
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['custom_field_id']);
                }
            }
        });
        Schema::dropIfExists('custom_fields');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
