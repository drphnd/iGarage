<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds users for tests. By default an approved renter.
 *
 * User::factory()->create()            approved renter
 * User::factory()->pending()->create() renter waiting for verification
 * User::factory()->rejected()->create()
 * User::factory()->admin()->create()
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_RENTER,
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->numerify('08##########'),
            'verification_status' => User::STATUS_APPROVED,
        ];
    }

    /**
     * Renter waiting for admin verification.
     */
    public function pending(): static
    {
        return $this->state(fn () => ['verification_status' => User::STATUS_PENDING]);
    }

    /**
     * Renter whose registration was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn () => ['verification_status' => User::STATUS_REJECTED]);
    }

    /**
     * Admin account.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'nik' => null,
            'phone' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
