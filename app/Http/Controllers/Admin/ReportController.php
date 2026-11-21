<?php

namespace App\Http\Controllers\Admin;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\RentExtend;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Admin: rental report for a year (per month) or a month (per day),
 * also broken down per garage type and per renter.
 *
 * Every amount is counted on the day it was paid (created_at):
 *   - rent fee       → when the rent was booked
 *   - charging fee   → when the charging session was requested
 *   - extension fee  → when the admin recorded the extension
 *
 * Rent revenue = rent fees + extension fees. Charging revenue = charging fees. Total = both.
 */
class ReportController
{
    /**
     * Show the report.
     *
     * Query string: ?mode=year&year=2026  or  ?mode=month&month=2026-11
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        [$mode, $from, $to] = $this->period($request);

        $rents = Rent::with(['garage', 'ride.user'])->whereBetween('created_at', [$from, $to])->get();
        $charges = EvCharge::with(['rent.garage', 'rent.ride.user'])->whereBetween('created_at', [$from, $to])->get();
        $extends = RentExtend::with(['rent.garage', 'rent.ride.user'])->whereBetween('created_at', [$from, $to])->get();

        // Whole period
        $summary = $this->summarize($rents, $charges, $extends);

        // Per month (year mode) or per day (month mode)
        $unit = $mode === 'year' ? 'month' : 'day';
        $slots = $mode === 'year' ? range(1, 12) : range(1, $from->daysInMonth);
        $byPeriod = [];

        foreach ($slots as $slot) {
            $inSlot = fn ($record) => $record->created_at->{$unit} === $slot;
            $byPeriod[$slot] = $this->summarize($rents->filter($inSlot), $charges->filter($inSlot), $extends->filter($inSlot));
        }

        // Per garage type (rents, renters, kWh and revenue per category)
        $byType = [];

        foreach (array_keys(Garage::TYPE_LABELS) as $type) {
            $ofType = fn ($record) => ($record instanceof Rent ? $record : $record->rent)->garage->type === $type;
            $byType[$type] = $this->summarize($rents->filter($ofType), $charges->filter($ofType), $extends->filter($ofType));
        }

        // Per renter, highest total first
        $renterIds = $rents->pluck('ride.user_id')
            ->merge($charges->pluck('rent.ride.user_id'))
            ->merge($extends->pluck('rent.ride.user_id'))
            ->unique();

        $byRenter = $renterIds->map(function ($userId) use ($rents, $charges, $extends) {
            $ofRenter = fn ($record) => ($record instanceof Rent ? $record : $record->rent)->ride->user_id === $userId;
            $rentsOfRenter = $rents->filter($ofRenter);
            $anyRecord = $rentsOfRenter->first() ?? $charges->first($ofRenter)?->rent ?? $extends->first($ofRenter)?->rent;

            return ['user' => $anyRecord->ride->user]
                + $this->summarize($rentsOfRenter, $charges->filter($ofRenter), $extends->filter($ofRenter));
        })->sortByDesc('total')->values();

        return view('admin.reports.index', compact('mode', 'from', 'summary', 'byPeriod', 'byType', 'byRenter'));
    }

    /**
     * Read the chosen period from the query string. Invalid input falls back to the current year or month.
     *
     * @param  Request  $request
     * @return array{0: string, 1: Carbon, 2: Carbon}  mode, first moment, last moment
     */
    private function period(Request $request): array
    {
        if ($request->query('mode') === 'month') {
            $month = (string) $request->query('month');
            $from = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
                ? Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth()
                : today()->startOfMonth();

            return ['month', $from, $from->copy()->endOfMonth()];
        }

        $year = (int) $request->query('year', today()->year);
        $year = $year >= 2000 && $year <= 2100 ? $year : today()->year;
        $from = Carbon::create($year)->startOfYear();

        return ['year', $from, $from->copy()->endOfYear()];
    }

    /**
     * Totals for one group of records.
     *
     * @param  Collection<int, Rent>  $rents
     * @param  Collection<int, EvCharge>  $charges
     * @param  Collection<int, RentExtend>  $extends
     * @return array{rents: int, renters: int, kwh: float, rent_revenue: int, charge_revenue: int, total: int}
     */
    private function summarize(Collection $rents, Collection $charges, Collection $extends): array
    {
        $rentRevenue = (int) $rents->sum('rent_fee') + (int) $extends->sum('amount_due');
        $chargeRevenue = (int) $charges->sum('fee');

        return [
            'rents' => $rents->count(),
            'renters' => $rents->pluck('ride.user_id')->unique()->count(),
            'kwh' => round((float) $charges->sum('charged_kwh'), 2),
            'rent_revenue' => $rentRevenue,
            'charge_revenue' => $chargeRevenue,
            'total' => $rentRevenue + $chargeRevenue,
        ];
    }
}
