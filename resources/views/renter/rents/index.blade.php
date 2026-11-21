@extends('layouts.app')

@section('title', 'My Rents')

@use('App\Models\Garage')
@use('App\Models\Rent')
@use('App\Models\Ride')

@section('content')
    <x-page-header eyebrow="Renter" title="My Rents" subtitle="Every garage you have rented, with what you paid.">
        <x-button href="{{ route('rents.create') }}">Rent a Garage</x-button>
    </x-page-header>

    @php
        $statuses = [
            Rent::STATUS_ACTIVE => ['blue', 'Active'],
            Rent::STATUS_UPCOMING => ['orange', 'Upcoming'],
            Rent::STATUS_FINISHED => ['gray', 'Finished'],
        ];
    @endphp

    @if ($rents->isEmpty())
        <x-card class="py-16 text-center">
            <p class="text-[17px] font-medium">No rents yet.</p>
            <p class="mt-2 text-[15px] text-ink-soft">Find a free garage for your vehicle in a few steps.</p>
            <x-button href="{{ route('rents.create') }}" class="mt-6">Rent a Garage</x-button>
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($rents as $rent)
                @php [$color, $label] = $statuses[$rent->status()]; @endphp

                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-6">
                        {{-- Garage and vehicle --}}
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-2xl font-semibold">{{ $rent->garage->code }}</span>
                                <x-badge :color="$color">{{ $label }}</x-badge>
                            </div>
                            <p class="mt-1 text-[13px] text-ink-soft">{{ Garage::TYPE_LABELS[$rent->garage->type] }} garage</p>
                            <p class="mt-4 text-[15px] font-medium">
                                {{ $rent->ride->brand }} {{ $rent->ride->model }}
                                @if ($rent->ride->plate_number)
                                    <span class="font-normal text-ink-soft">· {{ $rent->ride->plate_number }}</span>
                                @endif
                            </p>
                            <p class="text-[13px] text-ink-soft">{{ Ride::TYPE_LABELS[$rent->ride->type] }}</p>
                        </div>

                        {{-- Period --}}
                        <div>
                            <p class="text-[12px] text-ink-soft">{{ Rent::TYPE_LABELS[$rent->rent_type] }} · {{ $rent->days }} days</p>
                            <p class="mt-1 text-[15px] font-medium">
                                {{ $rent->start_date->format('d M Y') }} → {{ $rent->end_date->format('d M Y') }}
                            </p>
                            @if ($rent->rentExtends->isNotEmpty())
                                <p class="mt-1 text-[12px] text-accent">Extended {{ $rent->rentExtends->count() }}×</p>
                            @endif
                        </div>

                        {{-- Payment --}}
                        <dl class="min-w-56 text-sm">
                            <div class="flex justify-between gap-6 py-1">
                                <dt class="text-ink-soft">Rent</dt>
                                <dd><x-money :amount="$rent->rent_fee" /></dd>
                            </div>
                            @foreach ($rent->evCharges as $charge)
                                <div class="flex justify-between gap-6 py-1">
                                    <dt class="text-ink-soft">Charging {{ rtrim(rtrim(number_format($charge->charged_kwh, 2), '0'), '.') }} kWh</dt>
                                    <dd><x-money :amount="$charge->fee" /></dd>
                                </div>
                            @endforeach
                            @foreach ($rent->rentExtends as $extend)
                                <div class="flex justify-between gap-6 py-1">
                                    <dt class="text-ink-soft">Extension +{{ $extend->additional_days }} days</dt>
                                    <dd><x-money :amount="$extend->amount_due" /></dd>
                                </div>
                            @endforeach
                            <div class="mt-1 flex justify-between gap-6 border-t border-black/5 pt-2 font-semibold">
                                <dt>Total</dt>
                                <dd><x-money :amount="$rent->totalPaid()" /></dd>
                            </div>
                            <div class="pt-2 text-right">
                                <a href="{{ asset('storage/' . $rent->payment_proof) }}" target="_blank"
                                   class="text-[13px] text-accent hover:underline">Payment proof ›</a>
                            </div>
                        </dl>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
