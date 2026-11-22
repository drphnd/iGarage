<?php

use App\Models\Garage;
use App\Models\Rent;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

function makeGarage(string $type, int $number, int $dailyPrice = 40000, ?int $pricePerKwh = null): Garage
{
    return Garage::create([
        'code' => Garage::makeCode($type, $number),
        'type' => $type,
        'daily_price' => $dailyPrice,
        'price_per_kwh' => $pricePerKwh,
    ]);
}

function rentGarage(Garage $garage, string $start, string $end): Rent
{
    $ride = User::factory()->create()->rides()->create([
        'type' => Ride::TYPE_CAR, 'plate_number' => 'L 1234 AB', 'brand' => 'Toyota', 'model' => 'Avanza',
    ]);

    return Rent::create([
        'ride_id' => $ride->id,
        'garage_id' => $garage->id,
        'rent_type' => Rent::TYPE_DAILY,
        'days' => (int) now()->parse($start)->diffInDays($end),
        'start_date' => $start,
        'end_date' => $end,
        'rent_fee' => $garage->daily_price,
        'payment_proof' => 'payment-proofs/test.jpg',
    ]);
}

function rentPayload(Garage $garage, array $overrides = []): array
{
    return array_merge([
        'ride_type' => Ride::TYPE_CAR,
        'rent_type' => Rent::TYPE_DAILY,
        'start_date' => today()->addDay()->toDateString(),
        'days' => 3,
        'plate_number' => 'L 1234 AB',
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'garage_id' => $garage->id,
        'payment_proof' => \Illuminate\Http\UploadedFile::fake()->image('proof.jpg'),
    ], $overrides);
}

function evRent(User $renter, Garage $garage, string $start, string $end, float $capacity = 60): Rent
{
    $ride = $renter->rides()->create([
        'type' => Ride::TYPE_ELECTRIC_CAR, 'plate_number' => 'L 777 EV', 'brand' => 'Hyundai',
        'model' => 'Ioniq 5', 'battery_capacity_kwh' => $capacity,
    ]);

    return Rent::create([
        'ride_id' => $ride->id,
        'garage_id' => $garage->id,
        'rent_type' => Rent::TYPE_DAILY,
        'days' => (int) now()->parse($start)->diffInDays($end),
        'start_date' => $start,
        'end_date' => $end,
        'rent_fee' => $garage->daily_price,
        'payment_proof' => 'payment-proofs/test.jpg',
    ]);
}

function day(int $offset): string
{
    return today()->addDays($offset)->toDateString();
}
