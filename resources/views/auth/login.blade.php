@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <x-card class="p-10">
        <h1 class="text-3xl font-semibold tracking-tight">Masuk ke iGarage.</h1>
        <p class="mt-2 text-[15px] text-ink-soft">Kelola sewa garasimu dalam satu tempat.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <x-input label="Email" name="email" type="email" autocomplete="email" required autofocus />
            <x-input label="Password" name="password" type="password" autocomplete="current-password" required />

            <label class="flex items-center gap-2 text-[13px] text-ink-soft">
                <input type="checkbox" name="remember" class="size-4 rounded accent-accent"> Ingat saya
            </label>

            <x-button class="w-full py-3!">Masuk</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-[13px] text-ink-soft">
        Belum punya akun? <a href="{{ route('register') }}" class="text-accent hover:underline">Daftar sebagai tenant ›</a>
    </p>
@endsection
