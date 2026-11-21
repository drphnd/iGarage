@extends('layouts.guest')

@section('title', 'Log In')

@section('content')
    <x-card class="p-10">
        <h1 class="text-3xl font-semibold tracking-tight">Log in to iGarage.</h1>
        <p class="mt-2 text-[15px] text-ink-soft">Manage your garage rents in one place.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <x-input label="Email" name="email" type="email" autocomplete="email" required autofocus />
            <x-input label="Password" name="password" type="password" autocomplete="current-password" required />

            <label class="flex items-center gap-2 text-[13px] text-ink-soft">
                <input type="checkbox" name="remember" class="size-4 rounded accent-accent"> Remember me
            </label>

            <x-button class="w-full py-3!">Log In</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-[13px] text-ink-soft">
        Don't have an account? <a href="{{ route('register') }}" class="text-accent hover:underline">Register as a renter ›</a>
    </p>
@endsection
