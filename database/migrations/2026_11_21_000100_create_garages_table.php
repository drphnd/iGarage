<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garage units created in bulk by the admin.
 *
 * code: S001 (bicycle), C001 (car), E001 (electric), max 999 per type.
 * price_per_kwh is only filled for electric garages.
 * Availability is derived from rents dates, so there is no status column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->char('code', 4)->unique();
            $table->enum('type', ['bicycle', 'car', 'electric'])->index();
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('price_per_kwh')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
