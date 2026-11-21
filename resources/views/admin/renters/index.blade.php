@extends('layouts.app')

@section('title', 'Renter Verification')

@section('content')
    <x-page-header eyebrow="Admin" title="Renter Verification"
                   subtitle="Check the ID card data of new registrants, then approve or reject them." />

    @php
        $filters = [
            'pending' => ['label' => 'Pending', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Approved', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0],
            'all' => ['label' => 'All', 'count' => $counts->sum()],
        ];
        $badges = [
            'pending' => ['orange', 'Pending'],
            'approved' => ['green', 'Approved'],
            'rejected' => ['red', 'Rejected'],
        ];
    @endphp

    {{-- Status filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($filters as $key => $filter)
            <a href="{{ route('admin.renters.index', ['status' => $key]) }}"
               class="rounded-full px-4 py-2 text-[13px] font-medium transition duration-200 {{ $status === $key ? 'bg-ink text-white' : 'bg-white text-ink-soft hover:text-ink' }}">
                {{ $filter['label'] }} <span class="ml-1 opacity-60">{{ $filter['count'] }}</span>
            </a>
        @endforeach
    </div>

    <x-card class="overflow-hidden p-0">
        @if ($renters->isEmpty())
            <div class="px-8 py-16 text-center">
                <p class="text-[17px] font-medium">No renters here.</p>
                <p class="mt-2 text-[15px] text-ink-soft">
                    {{ $status === 'pending' ? 'Every registration has been verified.' : 'Try another filter.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Renter</th>
                            <th class="px-4 py-4 font-medium">NIK</th>
                            <th class="px-4 py-4 font-medium">Phone</th>
                            <th class="px-4 py-4 font-medium">Registered</th>
                            <th class="px-4 py-4 font-medium">Status</th>
                            <th class="px-8 py-4 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($renters as $renter)
                            @php [$badgeColor, $badgeLabel] = $badges[$renter->verification_status]; @endphp
                            <tr>
                                <td class="px-8 py-5">
                                    <p class="font-medium">{{ $renter->name }}</p>
                                    <p class="text-[13px] text-ink-soft">{{ $renter->email }}</p>
                                </td>
                                <td class="px-4 py-5 font-mono text-[13px]">{{ $renter->nik }}</td>
                                <td class="px-4 py-5">{{ $renter->phone }}</td>
                                <td class="px-4 py-5 text-ink-soft">{{ $renter->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-5"><x-badge :color="$badgeColor">{{ $badgeLabel }}</x-badge></td>
                                <td class="px-8 py-5">
                                    @if ($renter->verification_status === 'pending')
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.renters.reject', $renter) }}"
                                                  onsubmit="return confirm('Reject this renter\'s registration?')">
                                                @csrf
                                                @method('PATCH')
                                                <x-button variant="danger" class="px-4! py-2!">Reject</x-button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.renters.approve', $renter) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button class="px-4! py-2!">Approve</x-button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="text-right text-[13px] text-ink-soft">Done</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="mt-6">{{ $renters->links() }}</div>
@endsection
