<?php

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;

/*
| Admin report: counts, kWh and revenue per period, per garage type and per renter.
| Helpers makeGarage(), rentGarage(), evRent() and day() live in tests/Pest.php.
*/

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    // Car rent: Rp 40.000, extended by 2 days for Rp 80.000.
    $this->carRent = rentGarage(makeGarage(Garage::TYPE_CAR, 1, 40000), day(0), day(1));
    $this->carRent->rentExtends()->create([
        'old_end_date' => day(1), 'new_end_date' => day(3), 'additional_days' => 2,
        'amount_due' => 80000, 'payment_proof' => 'payment-proofs/test.jpg',
    ]);

    // Electric rent: Rp 50.000 plus two charging sessions (40 kWh + 10 kWh at Rp 2.500).
    $this->evRenter = User::factory()->create(['name' => 'Eva Listrik']);
    $this->evRent = evRent($this->evRenter, makeGarage(Garage::TYPE_ELECTRIC, 1, 50000, 2500), day(0), day(2));
    foreach ([40, 10] as $kwh) {
        $this->evRent->evCharges()->create([
            'battery_level_kwh' => 60 - $kwh, 'charged_kwh' => $kwh, 'price_per_kwh' => 2500,
            'fee' => $kwh * 2500, 'payment_proof' => 'payment-proofs/test.jpg',
        ]);
    }

    // A rent paid last year must not be counted this year.
    $old = rentGarage(makeGarage(Garage::TYPE_BICYCLE, 1, 10000), day(0), day(1));
    $old->created_at = now()->subYear();
    $old->save();
});

test('the yearly report sums rents, kWh and revenue for the year', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index', ['mode' => 'year', 'year' => today()->year]))
        ->assertOk()
        ->assertViewHas('summary', fn ($summary) => $summary['rents'] === 2
            && $summary['renters'] === 2
            && $summary['kwh'] === 50.0
            && $summary['rent_revenue'] === 170000      // 40.000 + 80.000 + 50.000
            && $summary['charge_revenue'] === 125000    // 50 kWh × 2.500
            && $summary['total'] === 295000)
        ->assertViewHas('byPeriod', fn ($rows) => count($rows) === 12
            && $rows[today()->month]['total'] === 295000);
});

test('the monthly report has one row per day of the month', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index', ['mode' => 'month', 'month' => today()->format('Y-m')]))
        ->assertOk()
        ->assertViewHas('byPeriod', fn ($rows) => count($rows) === today()->daysInMonth
            && $rows[today()->day]['rents'] === 2
            && $rows[today()->day]['total'] === 295000);
});

test('the report is broken down per garage type', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index'))
        ->assertOk()
        ->assertViewHas('byType', fn ($rows) => $rows[Garage::TYPE_CAR]['rents'] === 1
            && $rows[Garage::TYPE_CAR]['rent_revenue'] === 120000
            && $rows[Garage::TYPE_ELECTRIC]['renters'] === 1
            && $rows[Garage::TYPE_ELECTRIC]['kwh'] === 50.0
            && $rows[Garage::TYPE_ELECTRIC]['total'] === 175000
            && $rows[Garage::TYPE_BICYCLE]['rents'] === 0);
});

test('the report is broken down per renter, highest total first', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Eva Listrik')
        ->assertViewHas('byRenter', fn ($rows) => $rows->count() === 2
            && $rows[0]['user']->is($this->evRenter)
            && $rows[0]['total'] === 175000);
});

test('a past year only contains what was paid in that year', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index', ['mode' => 'year', 'year' => today()->year - 1]))
        ->assertOk()
        ->assertViewHas('summary', fn ($summary) => $summary['rents'] === 1 && $summary['total'] === 10000);
});

test('invalid report filters fall back to the current period', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index', ['mode' => 'month', 'month' => '2026-13']))
        ->assertOk()
        ->assertViewHas('from', fn ($from) => $from->isSameMonth(today()));

    $this->actingAs($this->admin)->get(route('admin.reports.index', ['year' => 'abc']))
        ->assertOk()
        ->assertViewHas('from', fn ($from) => $from->year === today()->year);
});

test('a renter cannot open the report', function () {
    $this->actingAs($this->evRenter)->get(route('admin.reports.index'))->assertForbidden();
});
