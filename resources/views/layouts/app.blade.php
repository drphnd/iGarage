<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'iGarage') · iGarage</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    @php
        $user = auth()->user();

        $menu = match (true) {
            $user->isAdmin() => [
                ['label' => 'Garages', 'route' => 'admin.garages.index', 'active' => 'admin.garages.*'],
                ['label' => 'Renter Verification', 'route' => 'admin.renters.index', 'active' => 'admin.renters.*'],
                ['label' => 'Extensions', 'route' => 'admin.extends.index', 'active' => 'admin.extends.*'],
                ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'],
            ],
            $user->isApproved() => [
                ['label' => 'My Rents', 'route' => 'my-rents', 'active' => 'my-rents'],
                ['label' => 'Rent a Garage', 'route' => 'rents.create', 'active' => 'rents.*'],
            ],
            default => [],
        };
    @endphp

    <nav class="sticky top-0 z-40 border-b border-black/5 bg-white/75 backdrop-blur-xl">
        <div class="mx-auto flex h-13 max-w-6xl items-center gap-8 px-6">
            <a href="{{ route('home') }}" class="text-[17px] font-semibold tracking-tight">iGarage</a>

            <div class="flex items-center gap-6">
                @foreach ($menu as $link)
                    <a href="{{ route($link['route']) }}"
                       class="text-[13px] transition duration-200 {{ request()->routeIs($link['active']) ? 'text-ink font-medium' : 'text-ink-soft hover:text-ink' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="ml-auto flex items-center gap-4 text-[13px]">
                <span class="text-ink-soft">{{ $user->name }}</span>
                <x-badge :color="$user->isAdmin() ? 'blue' : 'gray'">{{ $user->isAdmin() ? 'Admin' : 'Renter' }}</x-badge>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-accent transition hover:underline">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-6 py-12">
        <x-flash />
        @yield('content')
    </main>
</body>
</html>
