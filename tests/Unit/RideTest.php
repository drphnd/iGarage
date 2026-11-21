<?php

use App\Models\Garage;
use App\Models\Ride;

/*
| Unit tests for the Ride model (no database).
*/

test('normalizePlate removes spaces and makes the plate uppercase', function () {
    expect(Ride::normalizePlate('l 1231 kk'))->toBe('L1231KK')
        ->and(Ride::normalizePlate('L1231KK'))->toBe('L1231KK')
        ->and(Ride::normalizePlate('  B  1  XY  '))->toBe('B1XY');
});

test('normalizePlate returns null for an empty plate', function () {
    expect(Ride::normalizePlate(''))->toBeNull()
        ->and(Ride::normalizePlate('   '))->toBeNull()
        ->and(Ride::normalizePlate(null))->toBeNull();
});

test('each vehicle type may only park in its allowed garage types', function (string $rideType, array $allowed, array $notAllowed) {
    $ride = new Ride(['type' => $rideType]);

    foreach ($allowed as $garageType) {
        expect($ride->canParkIn($garageType))->toBeTrue();
    }
    foreach ($notAllowed as $garageType) {
        expect($ride->canParkIn($garageType))->toBeFalse();
    }
})->with([
    'bicycle' => [Ride::TYPE_BICYCLE, [Garage::TYPE_BICYCLE], [Garage::TYPE_CAR, Garage::TYPE_ELECTRIC]],
    'motorcycle' => [Ride::TYPE_MOTORCYCLE, [Garage::TYPE_BICYCLE], [Garage::TYPE_CAR, Garage::TYPE_ELECTRIC]],
    'car' => [Ride::TYPE_CAR, [Garage::TYPE_CAR], [Garage::TYPE_BICYCLE, Garage::TYPE_ELECTRIC]],
    'electric car' => [Ride::TYPE_ELECTRIC_CAR, [Garage::TYPE_CAR, Garage::TYPE_ELECTRIC], [Garage::TYPE_BICYCLE]],
]);

test('only an electric car is electric', function () {
    expect((new Ride(['type' => Ride::TYPE_ELECTRIC_CAR]))->isElectric())->toBeTrue()
        ->and((new Ride(['type' => Ride::TYPE_CAR]))->isElectric())->toBeFalse();
});
