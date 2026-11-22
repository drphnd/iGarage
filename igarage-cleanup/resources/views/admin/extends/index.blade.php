@extends('layouts.app')

@section('title', 'Extensions')

@use('App\Models\Garage')

@section('content')
    <x-page-header eyebrow="Admin" title="Extensions"
                   subtitle="Rents that are active today. Extend one after the renter has paid for the extra days." />

    <x-card class="overflow-hidden p-0">
        @if ($rents->isEmpty())
            <div class="px-8 py-16 text-center">
                <p class="text-[17px] font-medium">No active rents today.</p>
                <p class="mt-2 text-[15px] text-ink-soft">Only a vehicle that is in a garage today can be extended.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Garage</th>
                            <th class="px-4 py-4 font-medium">Renter</th>
                            <th class="px-4 py-4 font-medium">Vehicle</th>
                            <th class="px-4 py-4 font-medium">Period</th>
                            <th class="px-8 py-4 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($rents as $rent)
                            <tr>
                                <td class="px-8 py-5">
                                    <p class="font-mono text-[15px] font-medium">{{ $rent->garage->code }}</p>
                                    <p class="text-[12px] text-ink-soft">{{ Garage::TYPE_LABELS[$rent->garage->type] }}</p>
                                </td>
                                <td class="px-4 py-5">
                                    <p class="font-medium">{{ $rent->ride->user->name }}</p>
                                    <p class="text-[12px] text-ink-soft">{{ $rent->ride->user->phone }}</p>
                                </td>
                                <td class="px-4 py-5">
                                    <p>{{ $rent->ride->brand }} {{ $rent->ride->model }}</p>
                                    <p class="text-[12px] text-ink-soft">{{ $rent->ride->plate_number ?? 'No plate' }}</p>
                                </td>
                                <td class="px-4 py-5">
                                    <p>{{ $rent->start_date->format('d M') }} → <span class="font-medium">{{ $rent->end_date->format('d M Y') }}</span></p>
                                    <p class="text-[12px] text-ink-soft">{{ (int) today()->diffInDays($rent->end_date) }} day(s) left</p>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <x-button href="{{ route('admin.extends.create', $rent) }}" class="px-4! py-2!">Extend</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    @if ($recentExtends->isNotEmpty())
        <h2 class="mt-14 mb-4 text-2xl font-semibold tracking-tight">Recent extensions</h2>
        <x-card class="overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Garage</th>
                            <th class="px-4 py-4 font-medium">Renter</th>
                            <th class="px-4 py-4 font-medium">End date</th>
                            <th class="px-4 py-4 text-right font-medium">Extra days</th>
                            <th class="px-4 py-4 text-right font-medium">Amount</th>
                            <th class="px-8 py-4 text-right font-medium">Proof</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($recentExtends as $extend)
                            <tr>
                                <td class="px-8 py-4 font-mono font-medium">{{ $extend->rent->garage->code }}</td>
                                <td class="px-4 py-4">{{ $extend->rent->ride->user->name }}</td>
                                <td class="px-4 py-4">{{ $extend->old_end_date->format('d M') }} → {{ $extend->new_end_date->format('d M Y') }}</td>
                                <td class="px-4 py-4 text-right">+{{ $extend->additional_days }}</td>
                                <td class="px-4 py-4 text-right"><x-money :amount="$extend->amount_due" /></td>
                                <td class="px-8 py-4 text-right">
                                    <a href="{{ asset('storage/' . $extend->payment_proof) }}" target="_blank" class="text-accent hover:underline">View ›</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
