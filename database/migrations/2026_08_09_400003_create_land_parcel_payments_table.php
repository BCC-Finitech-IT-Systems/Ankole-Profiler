<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcel_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('paid_on');
            $table->string('payee')->nullable();
            $table->string('purpose')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('land_parcel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcel_payments');
    }
};
