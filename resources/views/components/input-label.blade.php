@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-ink-soft']) }}>
    {{ $value ?? $slot }}
</label>
