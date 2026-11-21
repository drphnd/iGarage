{{-- Error page layout. Stands alone (no navbar, no Vite) so it still renders when something else is broken. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · iGarage</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif;
               background: #f5f5f7; color: #1d1d1f; -webkit-font-smoothing: antialiased; }
        .box { text-align: center; padding: 2rem; max-width: 28rem; }
        .code { font-size: 5rem; font-weight: 600; letter-spacing: -0.04em; color: #d2d2d7; margin: 0; }
        h1 { margin: .25rem 0 .5rem; font-size: 2rem; font-weight: 600; letter-spacing: -0.02em; }
        p { color: #6e6e73; font-size: 17px; line-height: 1.5; }
        a { display: inline-block; margin-top: 1.5rem; padding: .7rem 1.4rem; border-radius: 999px;
            background: #0071e3; color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; }
        a:hover { background: #0077ed; }
    </style>
</head>
<body>
    <div class="box">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Back to Home</a>
    </div>
</body>
</html>
