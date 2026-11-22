@extends('layouts.app')

@section('title', 'Extend Rent')

@section('content')
    <x-page-header eyebrow="Admin · Extensions" title="Extend {{ $rent->garage->code }}"
                   subtitle="Choose the new end date and upload the renter's payment proof for the extra days." />

    @php
        $dailyPrice = $rent->garage->daily_price;
        $minDate = $rent->end_date->copy()->addDay()->toDateString();
        $dateHint = $maxEndDate
            ? 'At most ' . $maxEndDate->format('d M Y') . ', when the next renter starts in this garage.'
            : 'The vehicle leaves on this date.';
    @endphp

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <x-card class="p-10">
            @if ($maxEndDate && $maxEndDate->lte($rent->end_date))
                <p class="rounded-2xl bg-warn/10 px-5 py-4 text-[15px] text-warn">
                    Another renter starts in this garage on {{ $maxEndDate->format('d M Y') }}, right when this rent ends.
                    This rent cannot be extended.
                </p>
            @else
                <form method="POST" action="{{ route('admin.extends.store', $rent) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <x-input label="New end date" name="new_end_date" type="date" min="{{ $minDate }}"
                             max="{{ $maxEndDate?->toDateString() }}"
                             :hint="$dateHint"
                             required autofocus />

                    <div class="rounded-2xl bg-canvas px-6 py-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Extra days</span>
                            <span id="extra-days">0</span>
                        </div>
                        <div class="mt-1 flex justify-between text-sm">
                            <span class="text-ink-soft">Daily price</span>
                            <x-money :amount="$dailyPrice" />
                        </div>
                        <div class="mt-3 flex justify-between border-t border-black/5 pt-3 text-[17px] font-semibold">
                            <span>Amount due</span>
                            <span id="amount">Rp 0</span>
                        </div>
                    </div>

                    <div>
                        <label for="payment_proof" class="mb-1.5 block text-[13px] font-medium">Payment proof</label>
                        <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required
                               class="block w-full rounded-xl bg-canvas text-[15px] text-ink-soft file:mr-4 file:rounded-full file:border-0 file:bg-black/5 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-ink hover:file:bg-black/10">
                        @error('payment_proof')
                            <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-[13px] text-ink-soft">JPG, PNG or PDF, max 2 MB.</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-button>Extend Rent</x-button>
                        <x-button variant="secondary" href="{{ route('admin.extends.index') }}">Cancel</x-button>
                    </div>
                </form>

                <script>
                    const currentEnd = new Date('{{ $rent->end_date->toDateString() }}');
                    const dailyPrice = {{ $dailyPrice }};
                    const newEndInput = document.getElementById('new_end_date');

                    function updateEstimate() {
                        const newEnd = new Date(newEndInput.value);
                        const days = newEndInput.value ? Math.max(Math.round((newEnd - currentEnd) / 86400000), 0) : 0;
                        document.getElementById('extra-days').textContent = days;
                        document.getElementById('amount').textContent = 'Rp ' + (days * dailyPrice).toLocaleString('id-ID');
                    }

                    newEndInput.addEventListener('input', updateEstimate);
                    updateEstimate();
                </script>
            @endif
        </x-card>

        <x-card class="h-fit p-8">
            <p class="text-[13px] font-medium text-ink-soft">Renter</p>
            <p class="mt-1 text-[15px] font-medium">{{ $rent->ride->user->name }}</p>
            <p class="text-[13px] text-ink-soft">{{ $rent->ride->user->phone }}</p>

            <p class="mt-6 text-[13px] font-medium text-ink-soft">Vehicle</p>
            <p class="mt-1 text-[15px]">{{ $rent->ride->brand }} {{ $rent->ride->model }}</p>
            <p class="text-[13px] text-ink-soft">{{ $rent->ride->plate_number ?? 'No plate' }}</p>

            <p class="mt-6 text-[13px] font-medium text-ink-soft">Current period</p>
            <p class="mt-1 text-[15px]">{{ $rent->start_date->format('d M Y') }} → {{ $rent->end_date->format('d M Y') }}</p>
            @if ($rent->rentExtends->isNotEmpty())
                <p class="text-[13px] text-accent">Already extended {{ $rent->rentExtends->count() }}×</p>
            @endif
        </x-card>
    </div>
@endsection
