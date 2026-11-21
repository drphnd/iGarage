{{--
    Rupiah amount, e.g. <x-money :amount="40000" /> → Rp 40.000
--}}
@props(['amount'])

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>Rp {{ number_format((int) $amount, 0, ',', '.') }}</span>
