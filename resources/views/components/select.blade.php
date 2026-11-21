{{--
    Dropdown with label and validation message.
    <x-select label="Tipe garasi" name="type" :options="['car' => 'Mobil', 'electric' => 'Mobil Listrik']" placeholder="Pilih tipe" />
--}}
@props(['label', 'name', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])

@php $selected = (string) old($name, $value); @endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-[13px] font-medium">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}"
            {{ $attributes->merge(['class' => 'h-12 w-full appearance-none rounded-xl border bg-canvas px-4 text-[15px] transition duration-200 focus:bg-white focus:outline-none focus:ring-4 '
                . ($errors->has($name) ? 'border-danger focus:ring-danger/15' : 'border-transparent focus:border-accent focus:ring-accent/15')]) }}>
        @if ($placeholder)
            <option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1.5 text-[13px] text-danger">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-1.5 text-[13px] text-ink-soft">{{ $hint }}</p>
        @endif
    @enderror
</div>
