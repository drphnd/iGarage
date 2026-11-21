@extends('layouts.guest')

@section('title', 'Daftar')
@section('width', 'max-w-xl')

@section('content')
    <x-card class="p-10">
        <h1 class="text-3xl font-semibold tracking-tight">Daftar sebagai tenant.</h1>
        <p class="mt-2 text-[15px] text-ink-soft">Akunmu akan aktif setelah admin memverifikasi data KTP.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf
            <x-input label="NIK" name="nik" inputmode="numeric" maxlength="16" hint="16 digit sesuai KTP" required autofocus />
            <x-input label="Nama lengkap" name="name" autocomplete="name" required />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Nomor telepon" name="phone" type="tel" inputmode="numeric" placeholder="08xxxxxxxxxx" required />
                <x-input label="Email" name="email" type="email" autocomplete="email" hint="Dipakai untuk login" required />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Password" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter" required />
                <x-input label="Ulangi password" name="password_confirmation" type="password" autocomplete="new-password" required />
            </div>

            <x-button class="w-full py-3!">Daftar</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-[13px] text-ink-soft">
        Sudah punya akun? <a href="{{ route('login') }}" class="text-accent hover:underline">Masuk ›</a>
    </p>
@endsection
