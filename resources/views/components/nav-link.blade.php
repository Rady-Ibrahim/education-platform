@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center border-b-2 border-brand-600 px-1 pt-1 text-sm font-semibold text-brand-900 transition duration-150 ease-in-out focus:outline-none'
    : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-ink-muted transition duration-150 ease-in-out hover:border-brand-200 hover:text-brand-800 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
