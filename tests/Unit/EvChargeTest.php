<?php

use App\Models\EvCharge;

/*
| Unit tests for the EvCharge model (no database).
*/

test('kwhToFull is the capacity minus the current level', function () {
    expect(EvCharge::kwhToFull(60, 15))->toBe(45.0)
        ->and(EvCharge::kwhToFull(75.5, 20.25))->toBe(55.25);
});

test('kwhToFull is never negative', function () {
    expect(EvCharge::kwhToFull(60, 60))->toBe(0.0)
        ->and(EvCharge::kwhToFull(60, 70))->toBe(0.0);
});

test('the charging fee is kWh × price per kWh, rounded to whole Rupiah', function () {
    expect(EvCharge::feeFor(45, 2500))->toBe(112500)
        ->and(EvCharge::feeFor(10.5, 2500))->toBe(26250)
        ->and(EvCharge::feeFor(0.33, 1000))->toBe(330);
});

test('batteryPercent converts kWh to a percentage of the capacity', function () {
    expect((new EvCharge(['battery_level_kwh' => 15]))->batteryPercent(60))->toBe(25.0)
        ->and((new EvCharge(['battery_level_kwh' => 15]))->batteryPercent(0))->toBe(0.0);
});
