@extends('layouts.app')

@section('title', 'Reports')

@use('App\Models\Garage')

@section('content')
    @php
        $periodLabel = $mode === 'year' ? $from->format('Y') : $from->format('F Y');
        $kwh = fn ($value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
        $maxTotal = max(array_column($byPeriod, 'total')) ?: 1;
    @endphp

    <x-page-header eyebrow="Admin" title="Reports"
                   subtitle="Rents, charging and revenue for {{ $periodLabel }}. Each amount counts on the day it was paid.">
        <x-button variant="secondary" type="button" onclick="window.print()" class="print:hidden">Print</x-button>
    </x-page-header>

    {{-- Period picker --}}
    <x-card class="mb-10 p-6 print:hidden">
        <div class="flex flex-wrap items-end gap-6">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-end gap-3">
                <input type="hidden" name="mode" value="year">
                <div>
                    <label for="year" class="mb-1.5 block text-[13px] font-medium">Per month in a year</label>
                    <select id="year" name="year" class="h-11 rounded-xl bg-canvas px-4 text-[15px] focus:outline-none focus:ring-4 focus:ring-accent/15">
                        @foreach (range(today()->year - 3, today()->year + 1) as $year)
                            <option value="{{ $year }}" @selected($mode === 'year' && $from->year === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <x-button :variant="$mode === 'year' ? 'primary' : 'secondary'">Show year</x-button>
            </form>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-end gap-3">
                <input type="hidden" name="mode" value="month">
                <div>
                    <label for="month" class="mb-1.5 block text-[13px] font-medium">Per day in a month</label>
                    <input id="month" type="month" name="month" value="{{ $mode === 'month' ? $from->format('Y-m') : today()->format('Y-m') }}"
                           class="h-11 rounded-xl bg-canvas px-4 text-[15px] focus:outline-none focus:ring-4 focus:ring-accent/15">
                </div>
                <x-button :variant="$mode === 'month' ? 'primary' : 'secondary'">Show month</x-button>
            </form>
        </div>
    </x-card>

    {{-- Summary --}}
    <div class="mb-14 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['Rents', number_format($summary['rents'], 0, ',', '.')],
            ['Renters', number_format($summary['renters'], 0, ',', '.')],
            ['kWh sold', $kwh($summary['kwh'])],
        ] as [$label, $value])
            <x-card class="p-6">
                <p class="text-[13px] text-ink-soft">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">{{ $value }}</p>
            </x-card>
        @endforeach
        <x-card class="p-6">
            <p class="text-[13px] text-ink-soft">Rent revenue</p>
            <p class="mt-1 text-xl font-semibold tracking-tight"><x-money :amount="$summary['rent_revenue']" /></p>
        </x-card>
        <x-card class="p-6">
            <p class="text-[13px] text-ink-soft">Charging revenue</p>
            <p class="mt-1 text-xl font-semibold tracking-tight"><x-money :amount="$summary['charge_revenue']" /></p>
        </x-card>
        <x-card class="bg-ink! p-6 text-white">
            <p class="text-[13px] text-white/60">Total revenue</p>
            <p class="mt-1 text-xl font-semibold tracking-tight"><x-money :amount="$summary['total']" /></p>
        </x-card>
    </div>

    {{-- Per month / per day --}}
    <h2 class="mb-4 text-2xl font-semibold tracking-tight">{{ $mode === 'year' ? 'Per month' : 'Per day' }}</h2>
    <x-card class="mb-14 overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                        <th class="px-8 py-4 font-medium">{{ $mode === 'year' ? 'Month' : 'Day' }}</th>
                        <th class="px-4 py-4 text-right font-medium">Rents</th>
                        <th class="px-4 py-4 text-right font-medium">kWh</th>
                        <th class="px-4 py-4 text-right font-medium">Rent revenue</th>
                        <th class="px-4 py-4 text-right font-medium">Charging revenue</th>
                        <th class="px-4 py-4 text-right font-medium">Total</th>
                        <th class="w-40 px-8 py-4 font-medium print:hidden"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @foreach ($byPeriod as $slot => $row)
                        <tr class="{{ $row['total'] === 0 && $row['rents'] === 0 ? 'text-ink-soft' : '' }}">
                            <td class="px-8 py-3 font-medium">
                                {{ $mode === 'year' ? \Illuminate\Support\Carbon::create(null, $slot, 1)->format('F') : $from->copy()->day($slot)->format('D, d M') }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['rents'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $kwh($row['kwh']) }}</td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row['rent_revenue']" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row['charge_revenue']" /></td>
                            <td class="px-4 py-3 text-right font-medium"><x-money :amount="$row['total']" /></td>
                            <td class="px-8 py-3 print:hidden">
                                <div class="h-1.5 rounded-full bg-accent" style="width: {{ round($row['total'] / $maxTotal * 100) }}%"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Per garage type --}}
    <h2 class="mb-4 text-2xl font-semibold tracking-tight">Per garage type</h2>
    <x-card class="mb-14 overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                        <th class="px-8 py-4 font-medium">Garage type</th>
                        <th class="px-4 py-4 text-right font-medium">Rents</th>
                        <th class="px-4 py-4 text-right font-medium">Renters</th>
                        <th class="px-4 py-4 text-right font-medium">kWh</th>
                        <th class="px-4 py-4 text-right font-medium">Rent revenue</th>
                        <th class="px-4 py-4 text-right font-medium">Charging revenue</th>
                        <th class="px-8 py-4 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @foreach ($byType as $type => $row)
                        <tr>
                            <td class="px-8 py-4 font-medium">{{ Garage::TYPE_LABELS[$type] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums">{{ $row['rents'] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums">{{ $row['renters'] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums">{{ $kwh($row['kwh']) }}</td>
                            <td class="px-4 py-4 text-right"><x-money :amount="$row['rent_revenue']" /></td>
                            <td class="px-4 py-4 text-right"><x-money :amount="$row['charge_revenue']" /></td>
                            <td class="px-8 py-4 text-right font-medium"><x-money :amount="$row['total']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Per renter --}}
    <h2 class="mb-4 text-2xl font-semibold tracking-tight">Per renter</h2>
    <x-card class="overflow-hidden p-0">
        @if ($byRenter->isEmpty())
            <p class="px-8 py-12 text-center text-[15px] text-ink-soft">No renter activity in {{ $periodLabel }}.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Renter</th>
                            <th class="px-4 py-4 text-right font-medium">Rents</th>
                            <th class="px-4 py-4 text-right font-medium">kWh</th>
                            <th class="px-4 py-4 text-right font-medium">Rent revenue</th>
                            <th class="px-4 py-4 text-right font-medium">Charging revenue</th>
                            <th class="px-8 py-4 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($byRenter as $row)
                            <tr>
                                <td class="px-8 py-4">
                                    <p class="font-medium">{{ $row['user']->name }}</p>
                                    <p class="text-[12px] text-ink-soft">{{ $row['user']->email }}</p>
                                </td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ $row['rents'] }}</td>
                                <td class="px-4 py-4 text-right tabular-nums">{{ $kwh($row['kwh']) }}</td>
                                <td class="px-4 py-4 text-right"><x-money :amount="$row['rent_revenue']" /></td>
                                <td class="px-4 py-4 text-right"><x-money :amount="$row['charge_revenue']" /></td>
                                <td class="px-8 py-4 text-right font-medium"><x-money :amount="$row['total']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
