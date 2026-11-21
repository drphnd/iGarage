<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garage rents made by tenants. Resolves the many-to-many between rides and garages.
 *
 * The garage is occupied from start_date until (not including) end_date.
 * end_date is updated by the admin when a rent is extended.
 * rent_fee is stored as a transaction record, so later price changes do not alter it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->restrictOnDelete();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete();
            $table->enum('rent_type', ['daily', 'monthly']);
            $table->unsignedTinyInteger('days');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('rent_fee');
            $table->string('payment_proof');
            $table->timestamps();

            // Speeds up the date-overlap check used for availability and extensions.
            $table->index(['garage_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rents');
    }
};
