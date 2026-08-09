<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcel_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->date('reported_on');
            $table->text('notes')->nullable();
            $table->text('blockers')->nullable();
            $table->text('next_action')->nullable();
            $table->date('revised_expected_completion_date')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['land_parcel_id', 'reported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcel_updates');
    }
};
