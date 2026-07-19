@props([
    'active',
    'tabs' => [],
    'method' => 'setTab',
])

<nav {{ $attributes->merge(['class' => 'page-tabs', 'role' => 'tablist']) }}>
    @foreach ($tabs as $key => $tab)
        @php
            $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
            $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
            $isActive = $active === $key;
        @endphp
        <button
            type="button"
            role="tab"
            wire:click="{{ $method }}('{{ $key }}')"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @class(['page-tab-active' => $isActive, 'page-tab' => ! $isActive])
        >
            <span>{{ $label }}</span>
            @if ($badge !== null && $badge !== '' && (int) $badge > 0)
                <span @class([
                    'page-tab-badge',
                    'page-tab-badge-active' => $isActive,
                ])>{{ $badge }}</span>
            @endif
        </button>
    @endforeach
</nav>
