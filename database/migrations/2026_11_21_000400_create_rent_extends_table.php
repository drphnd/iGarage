<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extension history created by the admin. Each row is one extension of a rent.
 *
 * amount_due = additional_days x garage daily price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_extends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained()->cascadeOnDelete();
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->unsignedSmallInteger('additional_days');
            $table->unsignedInteger('amount_due');
            $table->string('payment_proof');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_extends');
    }
};
