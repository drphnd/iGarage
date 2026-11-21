{{--
    Multi-line text input with label, hint and validation message.
    <x-textarea label="Notes" name="notes" rows="4" />
--}}
@props(['label', 'name', 'value' => null, 'rows' => 3, 'hint' => null])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-[13px] font-medium">{{ $label }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              {{ $attributes->merge(['class' => 'w-full rounded-xl border bg-canvas px-4 py-3 text-[15px] transition duration-200 placeholder:text-ink-soft/60 focus:bg-white focus:outline-none focus:ring-4 '
                  . ($errors->has($name) ? 'border-danger focus:ring-danger/15' : 'border-transparent focus:border-accent focus:ring-accent/15')]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-1.5 text-[13px] text-ink-soft">{{ $hint }}</p>
        @endif
    @enderror
</div>
