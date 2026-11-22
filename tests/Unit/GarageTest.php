<?php

use App\Models\Garage;
use App\Models\Rent;

function rentFromTo(int $start, int $end): Rent
{
    return new Rent(['start_date' => today()->addDays($start), 'end_date' => today()->addDays($end)]);
}

test('makeCode builds a 4-character code from the type prefix and a 3-digit number', function () {
    expect(Garage::makeCode(Garage::TYPE_BICYCLE, 1))->toBe('S001')
        ->and(Garage::makeCode(Garage::TYPE_CAR, 42))->toBe('C042')
        ->and(Garage::makeCode(Garage::TYPE_ELECTRIC, 999))->toBe('E999');
});

test('the monthly price is 25 times the daily price', function () {
    expect((new Garage(['daily_price' => 40000]))->monthlyPrice())->toBe(1000000);
});

test('only an electric garage is electric', function () {
    expect((new Garage(['type' => Garage::TYPE_ELECTRIC]))->isElectric())->toBeTrue()
        ->and((new Garage(['type' => Garage::TYPE_CAR]))->isElectric())->toBeFalse();
});

test('statusOn is "in use" when a rent covers today', function () {
    $garage = (new Garage)->setRelation('rents', collect([rentFromTo(-1, 2)]));

    expect($garage->statusOn())->toBe(Garage::STATUS_IN_USE);
});

test('statusOn is "booked" when the only rent starts later', function () {
    $garage = (new Garage)->setRelation('rents', collect([rentFromTo(3, 5)]));

    expect($garage->statusOn())->toBe(Garage::STATUS_BOOKED);
});

test('statusOn is "available" with no rents or when the rent ends today', function () {
    expect((new Garage)->setRelation('rents', collect())->statusOn())->toBe(Garage::STATUS_AVAILABLE)
        ->and((new Garage)->setRelation('rents', collect([rentFromTo(-3, 0)]))->statusOn())->toBe(Garage::STATUS_AVAILABLE);
});
