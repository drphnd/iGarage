<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the role, renter identity data, and verification status to users.
 *
 * - role: admin | renter (new registrations are always renter)
 * - nik: 16-digit Indonesian ID number, only filled for renters
 * - verification_status: pending | approved | rejected (set by admin)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'renter'])->default('renter')->after('email');
            $table->char('nik', 16)->nullable()->unique()->after('role');
            $table->string('phone', 15)->nullable()->after('nik');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['role', 'nik', 'phone', 'verification_status']);
        });
    }
};
