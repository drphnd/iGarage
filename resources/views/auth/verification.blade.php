{{-- Shown to tenants who are not approved yet (pending) or were rejected. --}}
@extends('layouts.guest')

@section('title', 'Verifikasi Akun')

@section('content')
    <x-card class="p-10 text-center">
        @if ($user->verification_status === \App\Models\User::STATUS_REJECTED)
            <x-badge color="red">Ditolak</x-badge>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight">Pendaftaranmu ditolak.</h1>
            <p class="mt-3 text-[15px] text-ink-soft">
                Data KTP yang kamu kirim belum dapat kami verifikasi. Hubungi admin iGarage untuk informasi lebih lanjut.
            </p>
        @else
            <x-badge color="orange">Menunggu verifikasi</x-badge>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight">Akunmu sedang diverifikasi.</h1>
            <p class="mt-3 text-[15px] text-ink-soft">
                Admin sedang memeriksa data KTP-mu. Setelah disetujui, kamu bisa langsung menyewa garasi.
            </p>
        @endif

        <dl class="mt-8 divide-y divide-black/5 rounded-2xl bg-canvas px-5 text-left text-sm">
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Nama</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Email</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">NIK</dt><dd class="font-medium">{{ $user->nik }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Terdaftar</dt><dd class="font-medium">{{ $user->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <x-button variant="secondary">Logout</x-button>
        </form>
    </x-card>
@endsection
