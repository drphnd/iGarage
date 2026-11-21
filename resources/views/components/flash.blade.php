{{-- Flash messages from ->with('success', '...') or ->with('error', '...'). --}}
@if (session('success'))
    <div class="mb-8 rounded-2xl bg-ok/10 px-5 py-3.5 text-sm text-ok">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="mb-8 rounded-2xl bg-danger/10 px-5 py-3.5 text-sm text-danger">{{ session('error') }}</div>
@endif
