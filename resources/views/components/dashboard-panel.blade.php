@props(['name'])

<div
    x-show="tab === '{{ $name }}'"
    x-cloak
    role="tabpanel"
    {{ $attributes->merge(['class' => 'space-y-5']) }}
>
    {{ $slot }}
</div>
