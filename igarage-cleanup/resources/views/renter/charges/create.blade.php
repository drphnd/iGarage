@extends('layouts.app')

@section('title', 'Request Charging')

@section('content')
    <x-page-header eyebrow="My Rents · {{ $rent->garage->code }}" title="Request Charging"
                   subtitle="Enter your current battery level. We charge it to full and you pay per kWh." />

    @php
        $capacity = $rent->ride->battery_capacity_kwh;
        $price = $rent->garage->price_per_kwh;
    @endphp

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <x-card class="p-10">
            <form method="POST" action="{{ route('rents.charges.store', $rent) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <x-input label="Current battery level (kWh)" name="battery_level_kwh" type="number" step="0.01" min="0"
                         max="{{ $capacity }}" placeholder="20" hint="Battery capacity is {{ $capacity }} kWh" required autofocus />

                <div class="rounded-2xl bg-canvas px-6 py-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-ink-soft">To charge</span>
                        <span><span id="kwh">0</span> kWh</span>
                    </div>
                    <div class="mt-1 flex justify-between text-sm">
                        <span class="text-ink-soft">Price per kWh</span>
                        <x-money :amount="$price" />
                    </div>
                    <div class="mt-3 flex justify-between border-t border-black/5 pt-3 text-[17px] font-semibold">
                        <span>Charging fee</span>
                        <span id="fee">Rp 0</span>
                    </div>
                </div>

                <div>
                    <label for="payment_proof" class="mb-1.5 block text-[13px] font-medium">Payment proof</label>
                    <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required
                           class="block w-full rounded-xl bg-canvas text-[15px] text-ink-soft file:mr-4 file:rounded-full file:border-0 file:bg-black/5 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-ink hover:file:bg-black/10">
                    @error('payment_proof')
                        <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-[13px] text-ink-soft">JPG, PNG or PDF, max 2 MB. Transfer the charging fee above.</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button>Request Charging</x-button>
                    <x-button variant="secondary" href="{{ route('my-rents') }}">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <x-card class="h-fit p-8">
            <p class="text-[13px] font-medium text-ink-soft">Vehicle</p>
            <p class="mt-1 text-[15px] font-medium">{{ $rent->ride->brand }} {{ $rent->ride->model }}</p>
            <p class="text-[13px] text-ink-soft">{{ $rent->ride->plate_number }} · until {{ $rent->end_date->format('d M Y') }}</p>

            <p class="mt-6 text-[13px] font-medium text-ink-soft">Previous charging</p>
            @forelse ($rent->evCharges as $charge)
                <div class="mt-3 flex justify-between text-sm">
                    <span>{{ $charge->created_at->format('d M, H:i') }} · {{ (float) $charge->charged_kwh }} kWh</span>
                    <x-money :amount="$charge->fee" />
                </div>
            @empty
                <p class="mt-3 text-sm text-ink-soft">None yet.</p>
            @endforelse
        </x-card>
    </div>

    <script>
        const capacity = {{ $capacity }};
        const price = {{ $price }};
        const levelInput = document.getElementById('battery_level_kwh');

        function updateEstimate() {
            const level = parseFloat(levelInput.value) || 0;
            const kwh = Math.max(capacity - level, 0);
            document.getElementById('kwh').textContent = Number(kwh.toFixed(2));
            document.getElementById('fee').textContent = 'Rp ' + Math.round(kwh * price).toLocaleString('id-ID');
        }

        levelInput.addEventListener('input', updateEstimate);
        updateEstimate();
    </script>
@endsection
