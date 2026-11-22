@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $styles = [
        'primary' => 'bg-accent text-white hover:bg-accent-hover',
        'secondary' => 'bg-black/5 text-ink hover:bg-black/10',
        'danger' => 'bg-danger/10 text-danger hover:bg-danger/15',
        'link' => 'px-0! py-0! text-accent hover:underline',
    ];
    $class = 'inline-flex items-center justify-center gap-1 rounded-full px-5 py-2.5 text-sm font-medium '
        . 'transition duration-200 disabled:cursor-not-allowed disabled:opacity-40 '
        . ($styles[$variant] ?? $styles['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
