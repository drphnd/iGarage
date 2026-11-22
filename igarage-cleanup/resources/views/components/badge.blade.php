@props(['color' => 'gray'])

@php
    $colors = [
        'green' => 'bg-ok/10 text-ok',
        'orange' => 'bg-warn/10 text-warn',
        'red' => 'bg-danger/10 text-danger',
        'blue' => 'bg-accent/10 text-accent',
        'gray' => 'bg-black/5 text-ink-soft',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[12px] font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
