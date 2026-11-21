@extends('layouts.app')

@section('title', 'Garages')

@section('content')
    <x-page-header eyebrow="Admin" title="Garages" subtitle="Every garage unit and its status today.">
        <x-button href="{{ route('admin.garages.create') }}">Create Garages</x-button>
    </x-page-header>

    @php
        $statuses = [
            \App\Models\Garage::STATUS_AVAILABLE => ['green', 'Available'],
            \App\Models\Garage::STATUS_BOOKED => ['orange', 'Booked'],
            \App\Models\Garage::STATUS_IN_USE => ['blue', 'In use'],
        ];
    @endphp

    {{-- Summary --}}
    <div class="mb-10 grid gap-4 sm:grid-cols-4">
        <x-card class="p-6">
            <p class="text-[13px] text-ink-soft">Total</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight">{{ $garages->count() }}</p>
        </x-card>
        @foreach ($statuses as $key => $statusInfo)
            <x-card class="p-6">
                <p class="text-[13px] text-ink-soft">{{ $statusInfo[1] }}</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight">{{ $statusCounts[$key] ?? 0 }}</p>
            </x-card>
        @endforeach
    </div>

    {{-- Type filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.garages.index') }}"
           class="rounded-full px-4 py-2 text-[13px] font-medium transition duration-200 {{ $type === 'all' ? 'bg-ink text-white' : 'bg-white text-ink-soft hover:text-ink' }}">
            All <span class="ml-1 opacity-60">{{ $typeCounts->sum() }}</span>
        </a>
        @foreach (\App\Models\Garage::TYPE_LABELS as $key => $label)
            <a href="{{ route('admin.garages.index', ['type' => $key]) }}"
               class="rounded-full px-4 py-2 text-[13px] font-medium transition duration-200 {{ $type === $key ? 'bg-ink text-white' : 'bg-white text-ink-soft hover:text-ink' }}">
                {{ $label }} <span class="ml-1 opacity-60">{{ $typeCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-card class="overflow-hidden p-0">
        @if ($garages->isEmpty())
            <div class="px-8 py-16 text-center">
                <p class="text-[17px] font-medium">No garages yet.</p>
                <p class="mt-2 text-[15px] text-ink-soft">Create your first garages to start accepting rents.</p>
                <x-button href="{{ route('admin.garages.create') }}" class="mt-6">Create Garages</x-button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Code</th>
                            <th class="px-4 py-4 font-medium">Type</th>
                            <th class="px-4 py-4 text-right font-medium">Daily</th>
                            <th class="px-4 py-4 text-right font-medium">Monthly</th>
                            <th class="px-4 py-4 text-right font-medium">Per kWh</th>
                            <th class="px-8 py-4 font-medium">Status today</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($garages as $garage)
                            @php
                                $status = $garage->statusOn();
                                [$color, $label] = $statuses[$status];
                                // Rent covering today, or the next upcoming one.
                                $current = $garage->rents->first(fn ($rent) => $rent->start_date->lte(today()));
                                $next = $garage->rents->first(fn ($rent) => $rent->start_date->gt(today()));
                            @endphp
                            <tr>
                                <td class="px-8 py-5 font-mono text-[15px] font-medium">{{ $garage->code }}</td>
                                <td class="px-4 py-5">{{ \App\Models\Garage::TYPE_LABELS[$garage->type] }}</td>
                                <td class="px-4 py-5 text-right"><x-money :amount="$garage->daily_price" /></td>
                                <td class="px-4 py-5 text-right"><x-money :amount="$garage->monthlyPrice()" /></td>
                                <td class="px-4 py-5 text-right">
                                    @if ($garage->isElectric())
                                        <x-money :amount="$garage->price_per_kwh" />
                                    @else
                                        <span class="text-ink-soft">—</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <x-badge :color="$color">{{ $label }}</x-badge>
                                    @if ($status === \App\Models\Garage::STATUS_IN_USE)
                                        <p class="mt-1 text-[12px] text-ink-soft">until {{ $current->end_date->format('d M Y') }}</p>
                                    @elseif ($status === \App\Models\Garage::STATUS_BOOKED)
                                        <p class="mt-1 text-[12px] text-ink-soft">from {{ $next->start_date->format('d M Y') }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
