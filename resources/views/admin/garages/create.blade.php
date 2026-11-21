@extends('layouts.app')

@section('title', 'Create Garages')

@section('content')
    <x-page-header eyebrow="Admin · Garages" title="Create Garages"
                   subtitle="Add several garages of one type at once. Codes continue automatically from the last one." />

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <x-card class="p-10">
            <form method="POST" action="{{ route('admin.garages.store') }}" class="space-y-5">
                @csrf

                <x-select label="Garage type" name="type" :options="\App\Models\Garage::TYPE_LABELS"
                          placeholder="Choose a type" required />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input label="Quantity" name="quantity" type="number" min="1" max="999" value="1"
                             hint="How many garages to create" required />
                    <x-input label="Daily price (Rp)" name="daily_price" type="number" min="1000"
                             placeholder="40000" hint="Monthly price = 25 × daily price" required />
                </div>

                {{-- Shown only for electric garages (see the script below). --}}
                <div id="kwh-field">
                    <x-input label="Price per kWh (Rp)" name="price_per_kwh" type="number" min="1"
                             placeholder="2500" hint="Charging price, electric garages only" />
                </div>

                <div class="flex items-center gap-3 pt-3">
                    <x-button>Create Garages</x-button>
                    <x-button variant="secondary" href="{{ route('admin.garages.index') }}">Cancel</x-button>
                </div>
            </form>
        </x-card>

        {{-- Next code per type --}}
        <x-card class="h-fit p-8">
            <p class="text-[13px] font-medium text-ink-soft">Next code</p>
            <dl class="mt-4 space-y-4">
                @foreach (\App\Models\Garage::TYPE_LABELS as $type => $label)
                    <div class="flex items-center justify-between">
                        <dt class="text-sm">{{ $label }}</dt>
                        <dd class="text-right">
                            @if ($nextCodes[$type]['next'])
                                <span class="font-mono text-[15px] font-medium">{{ $nextCodes[$type]['next'] }}</span>
                                <span class="block text-[12px] text-ink-soft">{{ $nextCodes[$type]['remaining'] }} left</span>
                            @else
                                <x-badge color="red">Full</x-badge>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    </div>

    <script>
        // Show the kWh price only for electric garages. A disabled input is not sent with the form.
        const typeSelect = document.getElementById('type');
        const kwhField = document.getElementById('kwh-field');
        const kwhInput = document.getElementById('price_per_kwh');

        function toggleKwhField() {
            const isElectric = typeSelect.value === '{{ \App\Models\Garage::TYPE_ELECTRIC }}';
            kwhField.hidden = ! isElectric;
            kwhInput.disabled = ! isElectric;
            kwhInput.required = isElectric;
        }

        typeSelect.addEventListener('change', toggleKwhField);
        toggleKwhField();
    </script>
@endsection
