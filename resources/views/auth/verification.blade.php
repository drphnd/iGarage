{{-- Shown to renters who are not approved yet (pending) or were rejected. --}}
@extends('layouts.guest')

@section('title', 'Account Verification')

@section('content')
    <x-card class="p-10 text-center">
        @if ($user->verification_status === \App\Models\User::STATUS_REJECTED)
            <x-badge color="red">Rejected</x-badge>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight">Your registration was rejected.</h1>
            <p class="mt-3 text-[15px] text-ink-soft">
                We could not verify the ID card data you submitted. Please contact the iGarage admin for more information.
            </p>
        @else
            <x-badge color="orange">Waiting for verification</x-badge>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight">Your account is being verified.</h1>
            <p class="mt-3 text-[15px] text-ink-soft">
                An admin is checking your ID card data. Once approved, you can start renting a garage right away.
            </p>
        @endif

        <dl class="mt-8 divide-y divide-black/5 rounded-2xl bg-canvas px-5 text-left text-sm">
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Name</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Email</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">NIK</dt><dd class="font-medium">{{ $user->nik }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-ink-soft">Registered</dt><dd class="font-medium">{{ $user->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <x-button variant="secondary">Log Out</x-button>
        </form>
    </x-card>
@endsection
