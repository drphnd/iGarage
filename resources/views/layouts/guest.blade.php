{{-- Layout for login, register and the verification page: one centered card, no navbar. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'iGarage') · iGarage</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center px-6 py-16">
    <a href="{{ route('home') }}" class="mb-10 text-[21px] font-semibold tracking-tight">iGarage</a>

    <div class="w-full @yield('width', 'max-w-md')">
        <x-flash />
        @yield('content')
    </div>

    <p class="mt-10 text-[12px] text-ink-soft">© {{ date('Y') }} iGarage · Vehicle garage rental</p>
</body>
</html>
