{{-- White rounded surface. <x-card>...</x-card> or <x-card class="p-0">...</x-card> for tables. --}}
<div {{ $attributes->merge(['class' => 'rounded-3xl bg-white p-8 shadow-[0_2px_16px_rgba(0,0,0,0.04)]']) }}>
    {{ $slot }}
</div>
