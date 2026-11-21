<?php

use App\Models\Garage;
use App\Models\RentExtend;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| Admin extends rents that are active today, never into the next booking of the same garage.
| Helpers makeGarage(), rentGarage() and day() live in tests/Pest.php.
*/

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

test('the extensions page lists only rents that are active today', function () {
    rentGarage(makeGarage(Garage::TYPE_CAR, 1), day(-1), day(3));    // active
    rentGarage(makeGarage(Garage::TYPE_CAR, 2), day(2), day(5));     // upcoming
    rentGarage(makeGarage(Garage::TYPE_CAR, 3), day(-5), day(0));    // left today

    $this->actingAs($this->admin)->get(route('admin.extends.index'))
        ->assertOk()
        ->assertSee('C001')
        ->assertDontSee('C002')
        ->assertDontSee('C003');
});

test('admin extends a rent and the amount is extra days × daily price', function () {
    $rent = rentGarage(makeGarage(Garage::TYPE_CAR, 1, 40000), day(-1), day(3));

    $this->actingAs($this->admin)->get(route('admin.extends.create', $rent))->assertOk();

    $this->actingAs($this->admin)->post(route('admin.extends.store', $rent), [
        'new_end_date' => day(5),
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertRedirect(route('admin.extends.index'))
        ->assertSessionHas('success');

    $extend = RentExtend::first();
    expect($extend->additional_days)->toBe(2)
        ->and($extend->amount_due)->toBe(80000)
        ->and($extend->old_end_date->toDateString())->toBe(day(3))
        ->and($rent->fresh()->end_date->toDateString())->toBe(day(5));

    Storage::disk('public')->assertExists($extend->payment_proof);

    // The renter sees the extension on My Rents.
    $this->actingAs($rent->ride->user)->get(route('my-rents'))->assertSee('Extension +2 days');
});

test('the new end date must be after the current end date', function () {
    $rent = rentGarage(makeGarage(Garage::TYPE_CAR, 1), day(-1), day(3));

    foreach ([day(3), day(1)] as $date) {
        $this->actingAs($this->admin)->post(route('admin.extends.store', $rent), [
            'new_end_date' => $date,
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHasErrors('new_end_date');
    }

    expect(RentExtend::count())->toBe(0);
});

test('an extension cannot run into the next booking of the same garage', function () {
    $garage = makeGarage(Garage::TYPE_CAR, 1);
    $rent = rentGarage($garage, day(-1), day(3));
    rentGarage($garage, day(5), day(8));   // next renter arrives in 5 days

    $this->actingAs($this->admin)->post(route('admin.extends.store', $rent), [
        'new_end_date' => day(6),
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertSessionHasErrors('new_end_date');

    expect($rent->fresh()->end_date->toDateString())->toBe(day(3));

    // Up to the day the next renter arrives is fine.
    $this->actingAs($this->admin)->post(route('admin.extends.store', $rent), [
        'new_end_date' => day(5),
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertSessionHasNoErrors();

    expect($rent->fresh()->end_date->toDateString())->toBe(day(5));
});

test('only rents that are active today can be extended', function () {
    $upcoming = rentGarage(makeGarage(Garage::TYPE_CAR, 1), day(2), day(5));

    $this->actingAs($this->admin)->post(route('admin.extends.store', $upcoming), [
        'new_end_date' => day(7),
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertRedirect(route('admin.extends.index'))
        ->assertSessionHas('error');

    expect(RentExtend::count())->toBe(0);
});

test('the payment proof is required for an extension', function () {
    $rent = rentGarage(makeGarage(Garage::TYPE_CAR, 1), day(-1), day(3));

    $this->actingAs($this->admin)->post(route('admin.extends.store', $rent), [
        'new_end_date' => day(5),
    ])->assertSessionHasErrors('payment_proof');

    expect($rent->fresh()->end_date->toDateString())->toBe(day(3));
});

test('a renter cannot extend a rent', function () {
    $rent = rentGarage(makeGarage(Garage::TYPE_CAR, 1), day(-1), day(3));
    $renter = $rent->ride->user;

    $this->actingAs($renter)->get(route('admin.extends.index'))->assertForbidden();
    $this->actingAs($renter)->post(route('admin.extends.store', $rent), [
        'new_end_date' => day(5),
        'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertForbidden();

    expect(RentExtend::count())->toBe(0);
});
