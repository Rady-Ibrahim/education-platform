@props([
    'tabs' => [],
    'default' => null,
])

@php
    $defaultTab = $default ?? (array_key_first($tabs) ?: 'main');
@endphp

<div
    x-data="{ tab: @js($defaultTab) }"
    {{ $attributes->merge(['class' => 'space-y-5']) }}
>
    <div class="dashboard-tabs" role="tablist" aria-label="أقسام اللوحة">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                role="tab"
                :aria-selected="tab === '{{ $key }}'"
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'dashboard-tab-active' : 'dashboard-tab'"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{ $slot }}
</div>
