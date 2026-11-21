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
            <x-input label="Full name" name="name" autocomplete="name" maxlength="100" required />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Phone number" name="phone" type="tel" inputmode="numeric" placeholder="08xxxxxxxxxx" required />
                <x-input label="Email" name="email" type="email" autocomplete="email" placeholder="name@gmail.com" hint="Used to log in" required />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input label="Password" name="password" type="password" autocomplete="new-password" maxlength="64" required />
                <x-input label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" maxlength="64" required />
            </div>

            {{-- Password requirements, ticked live while typing (the server checks them again) --}}
            <ul id="password-rules" class="grid gap-1.5 rounded-2xl bg-canvas px-5 py-4 text-[13px] text-ink-soft sm:grid-cols-2">
                <li data-rule="length">At least 8 characters</li>
                <li data-rule="case">Uppercase and lowercase letters</li>
                <li data-rule="number">At least one number</li>
                <li data-rule="symbol">At least one symbol (!@#$…)</li>
                <li data-rule="match" class="sm:col-span-2">Both passwords match</li>
            </ul>

            <x-button class="w-full py-3!">Register</x-button>
        </form>
    </x-card>

    <script>
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const checks = {
            length: (value) => value.length >= 8,
            case: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: (value) => /[0-9]/.test(value),
            symbol: (value) => /[^A-Za-z0-9\s]/.test(value),
            match: (value) => value !== '' && value === confirmation.value,
        };

        function updateRules() {
            document.querySelectorAll('#password-rules li').forEach((item) => {
                const passed = checks[item.dataset.rule](password.value);
                item.classList.toggle('text-ok', passed);
                item.textContent = (passed ? '✓ ' : '• ') + item.textContent.replace(/^[✓•] /, '');
            });
        }

        password.addEventListener('input', updateRules);
        confirmation.addEventListener('input', updateRules);
        updateRules();
    </script>

    <p class="mt-6 text-center text-[13px] text-ink-soft">
        Already have an account? <a href="{{ route('login') }}" class="text-accent hover:underline">Log in ›</a>
    </p>
@endsection
