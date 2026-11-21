<?php

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\RentExtend;
use App\Models\Ride;
use Illuminate\Support\Carbon;

/*
| Unit tests for the Rent model (no database).
*/

test('a daily rent can be at most the days in the start month minus 1', function (string $start, int $max) {
    expect(Rent::maxDailyDays(Carbon::parse($start)))->toBe($max);
})->with([
    'November (30 days)' => ['2026-11-21', 29],
    'December (31 days)' => ['2026-12-01', 30],
    'February 2027 (28 days)' => ['2027-02-02', 27],
    'February 2028, leap year (29 days)' => ['2028-02-10', 28],
]);

test('a monthly rent lasts the number of days in the start month', function () {
    expect(Rent::daysFor(Rent::TYPE_MONTHLY, Carbon::parse('2026-11-21'), null))->toBe(30)
        ->and(Rent::daysFor(Rent::TYPE_MONTHLY, Carbon::parse('2027-02-02'), null))->toBe(28);
});

test('a daily rent lasts the number of days chosen', function () {
    expect(Rent::daysFor(Rent::TYPE_DAILY, Carbon::parse('2026-11-21'), 3))->toBe(3);
});

test('the rent fee is days × daily price, or 25 × daily price when monthly', function () {
    $garage = new Garage(['daily_price' => 40000]);

    expect(Rent::feeFor(Rent::TYPE_DAILY, 3, $garage))->toBe(120000)
        ->and(Rent::feeFor(Rent::TYPE_MONTHLY, 30, $garage))->toBe(1000000);
});

test('status is upcoming, active or finished depending on today', function () {
    $upcoming = new Rent(['start_date' => today()->addDay(), 'end_date' => today()->addDays(3)]);
    $active = new Rent(['start_date' => today()->subDay(), 'end_date' => today()->addDay()]);
    $endsToday = new Rent(['start_date' => today()->subDays(3), 'end_date' => today()]);

    expect($upcoming->status())->toBe(Rent::STATUS_UPCOMING)
        ->and($active->status())->toBe(Rent::STATUS_ACTIVE)
        ->and($endsToday->status())->toBe(Rent::STATUS_FINISHED);   // the vehicle leaves on the end date
});

test('totalDays counts from start to end, including extensions', function () {
    $rent = new Rent(['start_date' => '2026-11-21', 'end_date' => '2026-11-26']);

    expect($rent->totalDays())->toBe(5);
});

test('totalPaid adds the rent fee, charging fees and extension fees', function () {
    $rent = (new Rent(['rent_fee' => 100000]))
        ->setRelation('evCharges', collect([new EvCharge(['fee' => 12500]), new EvCharge(['fee' => 25000])]))
        ->setRelation('rentExtends', collect([new RentExtend(['amount_due' => 80000])]));

    expect($rent->totalPaid())->toBe(217500);
});

test('charging can only be requested for an active electric car in an electric garage', function () {
    $make = fn (string $rideType, string $garageType, int $startOffset) => (new Rent([
        'start_date' => today()->addDays($startOffset), 'end_date' => today()->addDays(3),
    ]))->setRelation('ride', new Ride(['type' => $rideType]))
        ->setRelation('garage', new Garage(['type' => $garageType]));

    expect($make(Ride::TYPE_ELECTRIC_CAR, Garage::TYPE_ELECTRIC, -1)->canRequestCharge())->toBeTrue()
        ->and($make(Ride::TYPE_ELECTRIC_CAR, Garage::TYPE_CAR, -1)->canRequestCharge())->toBeFalse()   // no charger
        ->and($make(Ride::TYPE_CAR, Garage::TYPE_ELECTRIC, -1)->canRequestCharge())->toBeFalse()       // not electric
        ->and($make(Ride::TYPE_ELECTRIC_CAR, Garage::TYPE_ELECTRIC, 1)->canRequestCharge())->toBeFalse(); // not started
});
