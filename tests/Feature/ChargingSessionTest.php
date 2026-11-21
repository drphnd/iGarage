<?php

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| Renter requests extra charging sessions for an electric car during an active rent.
| Helpers makeGarage() and evRent() live in tests/Pest.php.
*/

beforeEach(function () {
    Storage::fake('public');
    $this->renter = User::factory()->create();
    $this->garage = makeGarage(Garage::TYPE_ELECTRIC, 1, 50000, 2500);
    $this->yesterday = today()->subDay()->toDateString();
    $this->inThreeDays = today()->addDays(3)->toDateString();
});

test('an active electric rent in an electric garage shows the Request Charging button', function () {
    evRent($this->renter, $this->garage, $this->yesterday, $this->inThreeDays);

    $this->actingAs($this->renter)->get(route('my-rents'))
        ->assertOk()
        ->assertSee('Request Charging');
});

test('renter can request charging and pays (capacity - current level) × price per kWh', function () {
    $rent = evRent($this->renter, $this->garage, $this->yesterday, $this->inThreeDays, 60);

    $this->actingAs($this->renter)->get(route('rents.charges.create', $rent))->assertOk();

    $this->actingAs($this->renter)->post(route('rents.charges.store', $rent), [
        'battery_level_kwh' => 20,
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertRedirect(route('my-rents'))
        ->assertSessionHas('success');

    $charge = EvCharge::first();
    expect($charge->rent_id)->toBe($rent->id)
        ->and($charge->charged_kwh)->toBe(40.0)    // 60 - 20
        ->and($charge->fee)->toBe(100000);         // 40 × 2.500

    Storage::disk('public')->assertExists($charge->payment_proof);
});

test('the battery level must be between 0 and below the capacity', function () {
    $rent = evRent($this->renter, $this->garage, $this->yesterday, $this->inThreeDays, 60);

    foreach ([60, 75, -1] as $level) {
        $this->actingAs($this->renter)->post(route('rents.charges.store', $rent), [
            'battery_level_kwh' => $level,
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHasErrors('battery_level_kwh');
    }

    expect(EvCharge::count())->toBe(0);
});

test('the payment proof is required', function () {
    $rent = evRent($this->renter, $this->garage, $this->yesterday, $this->inThreeDays);

    $this->actingAs($this->renter)->post(route('rents.charges.store', $rent), [
        'battery_level_kwh' => 20,
    ])->assertSessionHasErrors('payment_proof');

    expect(EvCharge::count())->toBe(0);
});

test('charging is not possible in a garage without a charger', function () {
    $carGarage = makeGarage(Garage::TYPE_CAR, 1);
    $rent = evRent($this->renter, $carGarage, $this->yesterday, $this->inThreeDays);

    $this->actingAs($this->renter)->post(route('rents.charges.store', $rent), [
        'battery_level_kwh' => 20,
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertRedirect(route('my-rents'))
        ->assertSessionHas('error');

    expect(EvCharge::count())->toBe(0);
});

test('charging is not possible before the rent starts or after it ends', function () {
    $upcoming = evRent($this->renter, $this->garage, today()->addDay()->toDateString(), today()->addDays(3)->toDateString());
    $finished = evRent($this->renter, makeGarage(Garage::TYPE_ELECTRIC, 2, 50000, 2500),
        today()->subDays(5)->toDateString(), today()->toDateString());

    foreach ([$upcoming, $finished] as $rent) {
        $this->actingAs($this->renter)->get(route('rents.charges.create', $rent))
            ->assertRedirect(route('my-rents'))
            ->assertSessionHas('error');
    }

    expect(EvCharge::count())->toBe(0);
});

test('a renter cannot charge on someone else\'s rent', function () {
    $rent = evRent($this->renter, $this->garage, $this->yesterday, $this->inThreeDays);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('rents.charges.create', $rent))->assertNotFound();
    $this->actingAs($stranger)->post(route('rents.charges.store', $rent), [
        'battery_level_kwh' => 20,
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertNotFound();

    expect(EvCharge::count())->toBe(0);
});
