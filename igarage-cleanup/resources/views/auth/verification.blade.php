@extends('layouts.guest')

@section('title', 'Account Verification')

@section('content')
    <x-card class="p-10 text-center">
        <x-badge color="orange">Waiting for verification</x-badge>
        <h1 class="mt-5 text-3xl font-semibold tracking-tight">Your account is being verified.</h1>
        <p class="mt-3 text-[15px] text-ink-soft">
            An admin is checking your ID card data. Once approved, you go straight to your rents.
        </p>

        <dl class="mt-8 divide-y divide-black/5 rounded-2xl bg-canvas px-5 text-left text-sm">
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Name</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Email</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">NIK</dt><dd class="font-medium">{{ $user->nik }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Registered</dt><dd class="font-medium">{{ $user->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>

        <p class="mt-6 flex items-center justify-center gap-2 text-[13px] text-ink-soft">
            <span class="size-2 animate-pulse rounded-full bg-warn"></span>
            Checking again automatically every 10 seconds
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-button variant="secondary">Log Out</x-button>
        </form>
    </x-card>

    <script>
        setTimeout(() => window.location.reload(), 10000);
    </script>
@endsection
