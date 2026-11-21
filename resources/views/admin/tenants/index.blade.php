@extends('layouts.app')

@section('title', 'Verifikasi Tenant')

@section('content')
    <x-page-header eyebrow="Admin" title="Verifikasi Tenant"
                   subtitle="Periksa data KTP pendaftar baru, lalu terima atau tolak pendaftarannya." />

    @php
        $filters = [
            'pending' => ['label' => 'Menunggu', 'count' => $counts['pending'] ?? 0],
            'approved' => ['label' => 'Disetujui', 'count' => $counts['approved'] ?? 0],
            'rejected' => ['label' => 'Ditolak', 'count' => $counts['rejected'] ?? 0],
            'all' => ['label' => 'Semua', 'count' => $counts->sum()],
        ];
        $badges = [
            'pending' => ['orange', 'Menunggu'],
            'approved' => ['green', 'Disetujui'],
            'rejected' => ['red', 'Ditolak'],
        ];
    @endphp

    {{-- Status filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($filters as $key => $filter)
            <a href="{{ route('admin.tenants.index', ['status' => $key]) }}"
               class="rounded-full px-4 py-2 text-[13px] font-medium transition duration-200 {{ $status === $key ? 'bg-ink text-white' : 'bg-white text-ink-soft hover:text-ink' }}">
                {{ $filter['label'] }} <span class="ml-1 opacity-60">{{ $filter['count'] }}</span>
            </a>
        @endforeach
    </div>

    <x-card class="overflow-hidden p-0">
        @if ($tenants->isEmpty())
            <div class="px-8 py-16 text-center">
                <p class="text-[17px] font-medium">Tidak ada tenant di sini.</p>
                <p class="mt-2 text-[15px] text-ink-soft">
                    {{ $status === 'pending' ? 'Semua pendaftaran sudah diverifikasi.' : 'Coba pilih filter lain.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-black/5 text-[12px] text-ink-soft">
                            <th class="px-8 py-4 font-medium">Tenant</th>
                            <th class="px-4 py-4 font-medium">NIK</th>
                            <th class="px-4 py-4 font-medium">Telepon</th>
                            <th class="px-4 py-4 font-medium">Terdaftar</th>
                            <th class="px-4 py-4 font-medium">Status</th>
                            <th class="px-8 py-4 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach ($tenants as $tenant)
                            @php [$badgeColor, $badgeLabel] = $badges[$tenant->verification_status]; @endphp
                            <tr>
                                <td class="px-8 py-5">
                                    <p class="font-medium">{{ $tenant->name }}</p>
                                    <p class="text-[13px] text-ink-soft">{{ $tenant->email }}</p>
                                </td>
                                <td class="px-4 py-5 font-mono text-[13px]">{{ $tenant->nik }}</td>
                                <td class="px-4 py-5">{{ $tenant->phone }}</td>
                                <td class="px-4 py-5 text-ink-soft">{{ $tenant->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-5"><x-badge :color="$badgeColor">{{ $badgeLabel }}</x-badge></td>
                                <td class="px-8 py-5">
                                    @if ($tenant->verification_status === 'pending')
                                        <div class="flex justify-end gap-2">
                                            <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}"
                                                  onsubmit="return confirm('Tolak pendaftaran tenant ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <x-button variant="danger" class="px-4! py-2!">Tolak</x-button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button class="px-4! py-2!">Terima</x-button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="text-right text-[13px] text-ink-soft">Selesai</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="mt-6">{{ $tenants->links() }}</div>
@endsection
