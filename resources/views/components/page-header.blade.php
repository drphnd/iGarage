@props(['title', 'eyebrow' => null, 'subtitle' => null])

<div class="mb-10 flex flex-wrap items-end justify-between gap-6">
    <div>
        @if ($eyebrow)
            <p class="mb-2 text-[13px] font-medium text-ink-soft">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-4xl font-semibold tracking-tight md:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-3 max-w-2xl text-[17px] text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="flex items-center gap-3">{{ $slot }}</div>
    @endif
</div>
