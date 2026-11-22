@extends('layouts.app')

@section('title', 'Rent a Garage')

@use('App\Models\Garage')
@use('App\Models\Rent')
@use('App\Models\Ride')

@php
    $rideType = request('ride_type');
    $rentType = request('rent_type', Rent::TYPE_DAILY);
    $isElectricCar = $rideType === Ride::TYPE_ELECTRIC_CAR;
@endphp

@section('content')
    <x-page-header eyebrow="Renter" title="Rent a Garage"
                   subtitle="Choose your vehicle and dates, check which garages are free, then pay and book." />

    <x-card class="p-10">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex size-7 items-center justify-center rounded-full bg-ink text-[13px] font-semibold text-white">1</span>
            <h2 class="text-xl font-semibold tracking-tight">When and what</h2>
        </div>

        <form method="GET" action="{{ route('rents.create') }}" class="grid gap-5 md:grid-cols-4 md:items-start">
            <x-select label="Vehicle type" name="ride_type" :options="Ride::TYPE_LABELS" :value="$rideType"
                      placeholder="Choose a vehicle" required />
            <x-select label="Rent type" name="rent_type" :options="Rent::TYPE_LABELS" :value="$rentType" required />
            <x-input label="Start date" name="start_date" type="date" min="{{ today()->toDateString() }}"
                     :value="request('start_date', today()->toDateString())" required />
            <div id="days-field">
                <x-input label="Days" name="days" type="number" min="1" :value="request('days', 1)"
                         hint="Up to the days in the start month minus 1" />
            </div>

            <div class="md:col-span-4">
                <x-button>Check Available Garages</x-button>
            </div>
        </form>
    </x-card>

    @if ($period)
        <x-card class="mt-6 p-10">
            <div class="mb-2 flex items-center gap-3">
                <span class="flex size-7 items-center justify-center rounded-full bg-ink text-[13px] font-semibold text-white">2</span>
                <h2 class="text-xl font-semibold tracking-tight">Choose a garage and pay</h2>
            </div>
            <p class="mb-8 text-[15px] text-ink-soft">
                {{ Ride::TYPE_LABELS[$rideType] }} · {{ Rent::TYPE_LABELS[$rentType] }} ·
                {{ $period['start']->format('d M Y') }} → {{ $period['end']->format('d M Y') }} ({{ $period['days'] }} days).
                The vehicle leaves on the end date.
            </p>

            <form method="POST" action="{{ route('rents.store') }}" enctype="multipart/form-data" class="space-y-10">
                @csrf
                <input type="hidden" name="ride_type" value="{{ $rideType }}">
                <input type="hidden" name="rent_type" value="{{ $rentType }}">
                <input type="hidden" name="start_date" value="{{ $period['start']->toDateString() }}">
                @if ($rentType === Rent::TYPE_DAILY)
                    <input type="hidden" name="days" value="{{ $period['days'] }}">
                @endif

                <section>
                    <h3 class="mb-4 text-[13px] font-medium">Garage</h3>

                    @if ($garages->isEmpty())
                        <p class="rounded-2xl bg-canvas px-5 py-4 text-[15px] text-ink-soft">
                            There are no garages for this vehicle type yet.
                        </p>
                    @elseif ($garages->where('is_available', true)->isEmpty())
                        <p class="mb-4 rounded-2xl bg-warn/10 px-5 py-4 text-[15px] text-warn">
                            Every garage is taken for these dates. Try another start date or a shorter period.
                        </p>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($garages as $garage)
                            <label class="relative flex cursor-pointer flex-col rounded-2xl border border-black/10 p-5 transition duration-200 hover:border-black/20 has-checked:border-accent has-checked:ring-4 has-checked:ring-accent/15 has-disabled:cursor-not-allowed has-disabled:opacity-40">
                                <input type="radio" name="garage_id" value="{{ $garage->id }}" class="sr-only"
                                       @checked(old('garage_id') == $garage->id) @disabled(! $garage->is_available)>
                                <span class="flex items-center justify-between">
                                    <span class="font-mono text-xl font-semibold">{{ $garage->code }}</span>
                                    @if ($garage->is_available)
                                        <x-badge color="green">Available</x-badge>
                                    @else
                                        <x-badge>Not available</x-badge>
                                    @endif
                                </span>
                                <span class="mt-1 text-[13px] text-ink-soft">{{ Garage::TYPE_LABELS[$garage->type] }} garage</span>
                                <span class="mt-4 text-[17px] font-semibold"><x-money :amount="$garage->period_fee" /></span>
                                <span class="text-[12px] text-ink-soft">
                                    {{ $rentType === Rent::TYPE_MONTHLY ? '25 × ' : $period['days'] . ' × ' }}<x-money :amount="$garage->daily_price" />
                                </span>
                                @if ($garage->isElectric() && $isElectricCar)
                                    <span class="mt-2 text-[12px] text-accent">+ full charge at <x-money :amount="$garage->price_per_kwh" />/kWh</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('garage_id')
                        <p class="mt-3 text-[13px] text-danger">{{ $message }}</p>
                    @enderror
                </section>

                <section>
                    <h3 class="mb-4 text-[13px] font-medium">Vehicle</h3>
                    <div class="grid gap-5 md:grid-cols-3">
                        @if ($rideType !== Ride::TYPE_BICYCLE)
                            <x-input label="Plate number" name="plate_number" placeholder="L 1234 AB" required />
                        @endif
                        <x-input label="Brand" name="brand" placeholder="{{ $rideType === Ride::TYPE_BICYCLE ? 'Polygon' : 'Toyota' }}" required />
                        <x-input label="Model" name="model" placeholder="{{ $rideType === Ride::TYPE_BICYCLE ? 'Heist 2' : 'Avanza' }}" required />
                    </div>

                    @if ($isElectricCar)
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <x-input label="Battery capacity (kWh)" name="battery_capacity_kwh" type="number" step="0.01" min="1"
                                     placeholder="60" required />
                            <x-input label="Current battery level (kWh)" name="battery_level_kwh" type="number" step="0.01" min="0"
                                     placeholder="15" required />
                        </div>
                        <p class="mt-3 text-[13px] text-ink-soft">
                            In an electric garage your car is fully charged when it arrives.
                            Charging fee = (capacity − current level) × price per kWh, paid together with the rent.
                            In a car garage there is no charger, so no charging fee.
                        </p>
                    @endif
                </section>

                <section>
                    <h3 class="mb-4 text-[13px] font-medium">Payment</h3>
                    <div class="max-w-md">
                        <label for="payment_proof" class="mb-1.5 block text-[13px] font-medium">Payment proof</label>
                        <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required
                               class="block w-full rounded-xl bg-canvas text-[15px] text-ink-soft file:mr-4 file:rounded-full file:border-0 file:bg-black/5 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-ink hover:file:bg-black/10">
                        @error('payment_proof')
                            <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-[13px] text-ink-soft">JPG, PNG or PDF, max 2 MB. Transfer the garage price{{ $isElectricCar ? ' plus the charging fee' : '' }}.</p>
                        @enderror
                    </div>
                </section>

                <div class="flex items-center gap-3 border-t border-black/5 pt-8">
                    <x-button :disabled="$garages->where('is_available', true)->isEmpty()">Rent Garage</x-button>
                    <x-button variant="secondary" href="{{ route('my-rents') }}">Cancel</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <script>
        const rentType = document.getElementById('rent_type');
        const startDate = document.getElementById('start_date');
        const daysField = document.getElementById('days-field');
        const daysInput = document.getElementById('days');

        function updateDaysField() {
            const isDaily = rentType.value === '{{ Rent::TYPE_DAILY }}';
            daysField.hidden = ! isDaily;
            daysInput.disabled = ! isDaily;

            if (startDate.value) {
                const [year, month] = startDate.value.split('-').map(Number);
                daysInput.max = new Date(year, month, 0).getDate() - 1;
            }
        }

        rentType.addEventListener('change', updateDaysField);
        startDate.addEventListener('change', updateDaysField);
        updateDaysField();
    </script>
@endsection
