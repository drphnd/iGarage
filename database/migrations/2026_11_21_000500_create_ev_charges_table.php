<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charging sessions for electric cars parked in electric garages.
 *
 * The first full charge is created together with the rent; tenants can request more during the rent.
 * charged_kwh = battery capacity - battery_level_kwh, fee = charged_kwh x price_per_kwh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ev_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained()->cascadeOnDelete();
            $table->decimal('battery_level_kwh', 6, 2);
            $table->decimal('charged_kwh', 6, 2);
            $table->unsignedInteger('price_per_kwh');
            $table->unsignedInteger('fee');
            $table->string('payment_proof');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ev_charges');
    }
};
