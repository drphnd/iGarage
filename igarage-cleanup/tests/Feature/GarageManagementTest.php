<?php

use App\Models\Garage;
use App\Models\User;

test('admin sees the garage list with prices', function () {
    $admin = User::factory()->admin()->create();
    makeGarage(Garage::TYPE_CAR, 1, 40000);
    makeGarage(Garage::TYPE_ELECTRIC, 1, 50000, 2500);

    $this->actingAs($admin)->get(route('admin.garages.index'))
        ->assertOk()
        ->assertSee('C001')
        ->assertSee('E001')
        ->assertSee('Rp 1.000.000');
});

test('bulk create continues the codes from the last garage of that type', function () {
    $admin = User::factory()->admin()->create();
    makeGarage(Garage::TYPE_CAR, 1);
    makeGarage(Garage::TYPE_CAR, 2);

    $this->actingAs($admin)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_CAR,
        'quantity' => 3,
        'daily_price' => 45000,
    ])->assertRedirect(route('admin.garages.index', ['type' => Garage::TYPE_CAR]))
        ->assertSessionHas('success');

    expect(Garage::where('type', Garage::TYPE_CAR)->orderBy('code')->pluck('code')->all())
        ->toBe(['C001', 'C002', 'C003', 'C004', 'C005'])
        ->and(Garage::where('code', 'C005')->value('daily_price'))->toBe(45000);
});

test('electric garages require a price per kWh', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_ELECTRIC,
        'quantity' => 2,
        'daily_price' => 50000,
    ])->assertSessionHasErrors('price_per_kwh');

    expect(Garage::count())->toBe(0);
});

test('non-electric garages never store a price per kWh', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_BICYCLE,
        'quantity' => 1,
        'daily_price' => 10000,
        'price_per_kwh' => 2500,
    ])->assertSessionHasNoErrors();

    expect(Garage::first()->price_per_kwh)->toBeNull();
});

test('quantity must be at least 1 and the price must be valid', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_CAR,
        'quantity' => 0,
        'daily_price' => -5,
    ])->assertSessionHasErrors(['quantity', 'daily_price']);

    expect(Garage::count())->toBe(0);
});

test('a type cannot go past code 999', function () {
    $admin = User::factory()->admin()->create();
    makeGarage(Garage::TYPE_CAR, 998);

    $this->actingAs($admin)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_CAR,
        'quantity' => 2,
        'daily_price' => 40000,
    ])->assertSessionHasErrors('quantity');

    expect(Garage::count())->toBe(1);
});

test('garage status is derived from rents: in use, booked, or available', function () {
    $inUse = makeGarage(Garage::TYPE_CAR, 1);
    $booked = makeGarage(Garage::TYPE_CAR, 2);
    $available = makeGarage(Garage::TYPE_CAR, 3);
    $finished = makeGarage(Garage::TYPE_CAR, 4);

    rentGarage($inUse, today()->subDay()->toDateString(), today()->addDays(2)->toDateString());
    rentGarage($booked, today()->addDays(5)->toDateString(), today()->addDays(7)->toDateString());
    rentGarage($finished, today()->subDays(5)->toDateString(), today()->toDateString());

    expect($inUse->statusOn())->toBe(Garage::STATUS_IN_USE)
        ->and($booked->statusOn())->toBe(Garage::STATUS_BOOKED)
        ->and($available->statusOn())->toBe(Garage::STATUS_AVAILABLE)
        ->and($finished->statusOn())->toBe(Garage::STATUS_AVAILABLE);
});

test('availableBetween excludes garages with an overlapping rent', function () {
    $garage = makeGarage(Garage::TYPE_CAR, 1);
    rentGarage($garage, '2026-12-10', '2026-12-15');

    expect(Garage::availableBetween('2026-12-14', '2026-12-16')->count())->toBe(0)
        ->and(Garage::availableBetween('2026-12-05', '2026-12-10')->count())->toBe(1)
        ->and(Garage::availableBetween('2026-12-15', '2026-12-20')->count())->toBe(1);
});

test('a renter cannot open or use the garage admin pages', function () {
    $renter = User::factory()->create();

    $this->actingAs($renter)->get(route('admin.garages.create'))->assertForbidden();
    $this->actingAs($renter)->post(route('admin.garages.store'), [
        'type' => Garage::TYPE_CAR, 'quantity' => 1, 'daily_price' => 40000,
    ])->assertForbidden();

    expect(Garage::count())->toBe(0);
});
