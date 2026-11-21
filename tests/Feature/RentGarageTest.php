<?php

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/*
| Renter rents a garage: availability check, validation, fees, first charge, and My Rents.
| Helpers makeGarage(), rentGarage() and rentPayload() live in tests/Pest.php.
*/

beforeEach(function () {
    Storage::fake('public');   // uploaded files go to a temporary disk
});

test('the availability check marks garages taken in that period as not available', function () {
    $renter = User::factory()->create();
    $taken = makeGarage(Garage::TYPE_CAR, 1);
    makeGarage(Garage::TYPE_CAR, 2);
    makeGarage(Garage::TYPE_BICYCLE, 1);
    rentGarage($taken, today()->toDateString(), today()->addDays(5)->toDateString());

    $this->actingAs($renter)->get(route('rents.create', [
        'ride_type' => Ride::TYPE_CAR,
        'rent_type' => Rent::TYPE_DAILY,
        'start_date' => today()->addDay()->toDateString(),
        'days' => 2,
    ]))->assertOk()
        ->assertSee('C001')
        ->assertSee('C002')
        ->assertSee('Not available')
        ->assertDontSee('S001');   // a car cannot use a bicycle garage
});

test('renter can rent a garage daily and pays days × daily price', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1, 40000);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage))
        ->assertRedirect(route('my-rents'))
        ->assertSessionHas('success');

    $rent = Rent::first();
    expect($rent->days)->toBe(3)
        ->and($rent->rent_fee)->toBe(120000)
        ->and($rent->start_date->toDateString())->toBe(today()->addDay()->toDateString())
        ->and($rent->end_date->toDateString())->toBe(today()->addDays(4)->toDateString())
        ->and($rent->ride->user_id)->toBe($renter->id);

    Storage::disk('public')->assertExists($rent->payment_proof);
});

test('a monthly rent lasts exactly one calendar month and costs 25 × daily price', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1, 40000);
    $start = today()->addMonthNoOverflow()->startOfMonth();

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'rent_type' => Rent::TYPE_MONTHLY,
        'start_date' => $start->toDateString(),
        'days' => null,
    ]))->assertSessionHasNoErrors();

    $rent = Rent::first();
    expect($rent->days)->toBe($start->daysInMonth)
        ->and($rent->end_date->toDateString())->toBe($start->copy()->addMonth()->toDateString())
        ->and($rent->rent_fee)->toBe(1000000);
});

test('the start date cannot be in the past', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'start_date' => today()->subDay()->toDateString(),
    ]))->assertSessionHasErrors('start_date');

    expect(Rent::count())->toBe(0);
});

test('a daily rent cannot be a full month or longer', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1);
    $start = today()->addMonthNoOverflow()->startOfMonth();

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'start_date' => $start->toDateString(),
        'days' => $start->daysInMonth,   // max is daysInMonth - 1
    ]))->assertSessionHasErrors('days');

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'start_date' => $start->toDateString(),
        'days' => $start->daysInMonth - 1,
    ]))->assertSessionHasNoErrors();
});

test('a garage that is taken in the chosen period cannot be rented', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1);
    rentGarage($garage, today()->addDays(2)->toDateString(), today()->addDays(6)->toDateString());

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage))   // tomorrow + 3 days overlaps
        ->assertSessionHasErrors('garage_id');

    expect(Rent::count())->toBe(1);
});

test('a vehicle can only use the garage types allowed for it', function () {
    $renter = User::factory()->create();
    $carGarage = makeGarage(Garage::TYPE_CAR, 1);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($carGarage, [
        'ride_type' => Ride::TYPE_MOTORCYCLE,
    ]))->assertSessionHasErrors('garage_id');

    expect(Rent::count())->toBe(0);
});

test('an electric car in an electric garage gets a first full charge', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_ELECTRIC, 1, 50000, 2500);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'ride_type' => Ride::TYPE_ELECTRIC_CAR,
        'battery_capacity_kwh' => 60,
        'battery_level_kwh' => 15,
    ]))->assertSessionHasNoErrors();

    $charge = EvCharge::first();
    expect($charge->charged_kwh)->toBe(45.0)          // 60 - 15
        ->and($charge->fee)->toBe(112500)             // 45 × 2.500
        ->and($charge->price_per_kwh)->toBe(2500)
        ->and(Rent::first()->ride->battery_capacity_kwh)->toBe(60.0);
});

test('an electric car in a normal car garage is not charged', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'ride_type' => Ride::TYPE_ELECTRIC_CAR,
        'battery_capacity_kwh' => 60,
        'battery_level_kwh' => 15,
    ]))->assertSessionHasNoErrors();

    expect(Rent::count())->toBe(1)
        ->and(EvCharge::count())->toBe(0);
});

test('the battery level cannot be higher than the capacity', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_ELECTRIC, 1, 50000, 2500);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'ride_type' => Ride::TYPE_ELECTRIC_CAR,
        'battery_capacity_kwh' => 60,
        'battery_level_kwh' => 70,
    ]))->assertSessionHasErrors('battery_level_kwh');
});

test('the payment proof is required and must be an image or PDF', function () {
    $renter = User::factory()->create();
    $garage = makeGarage(Garage::TYPE_CAR, 1);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, ['payment_proof' => null]))
        ->assertSessionHasErrors('payment_proof');

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($garage, [
        'payment_proof' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 10),
    ]))->assertSessionHasErrors('payment_proof');

    expect(Rent::count())->toBe(0);
});

test('the same vehicle cannot be in two garages at the same time', function () {
    $renter = User::factory()->create();
    $first = makeGarage(Garage::TYPE_CAR, 1);
    $second = makeGarage(Garage::TYPE_CAR, 2);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($first))->assertSessionHasNoErrors();
    $this->actingAs($renter)->post(route('rents.store'), rentPayload($second))->assertSessionHasErrors('plate_number');

    expect(Rent::count())->toBe(1)
        ->and(Ride::count())->toBe(1);   // the vehicle is reused, not registered twice
});

test('My Rents only shows the rents of the signed-in renter', function () {
    $renter = User::factory()->create();
    $mine = makeGarage(Garage::TYPE_CAR, 1);
    $other = makeGarage(Garage::TYPE_CAR, 2);

    $this->actingAs($renter)->post(route('rents.store'), rentPayload($mine));
    rentGarage($other, today()->toDateString(), today()->addDays(2)->toDateString());   // another renter

    $this->actingAs($renter)->get(route('my-rents'))
        ->assertOk()
        ->assertSee('C001')
        ->assertDontSee('C002');
});
