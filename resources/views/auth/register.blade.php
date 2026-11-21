@extends('layouts.guest')

@section('title', 'Register')
@section('width', 'max-w-xl')

@section('content')
    <x-card class="p-10">
        <h1 class="text-3xl font-semibold tracking-tight">Register as a renter.</h1>
        <p class="mt-2 text-[15px] text-ink-soft">Your account becomes active once an admin verifies your ID card (KTP).</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf
            <x-input label="NIK" name="nik" inputmode="numeric" maxlength="16" hint="16 digits, as on your ID card" required autofocus />
            <x-input label="Full name" name="name" autocomplete="name" required />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Phone number" name="phone" type="tel" inputmode="numeric" placeholder="08xxxxxxxxxx" required />
                <x-input label="Email" name="email" type="email" autocomplete="email" hint="Used to log in" required />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Password" name="password" type="password" autocomplete="new-password" hint="At least 8 characters" required />
                <x-input label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" required />
            </div>

            <x-button class="w-full py-3!">Register</x-button>
        </form>
    </x-card>

    <p class="mt-6 text-center text-[13px] text-ink-soft">
        Already have an account? <a href="{{ route('login') }}" class="text-accent hover:underline">Log in ›</a>
    </p>
@endsection
