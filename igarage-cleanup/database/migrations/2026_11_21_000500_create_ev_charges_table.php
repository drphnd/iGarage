<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
