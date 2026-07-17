@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full border-s-4 border-brand-600 bg-brand-50 py-2 pe-4 ps-3 text-start text-base font-semibold text-brand-900 transition duration-150 ease-in-out focus:outline-none'
    : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-ink-muted transition duration-150 ease-in-out hover:border-brand-200 hover:bg-brand-50/70 hover:text-brand-900 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
