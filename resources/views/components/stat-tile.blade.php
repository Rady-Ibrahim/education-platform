@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'tone' => 'default',
])

@php
    $toneClass = match ($tone) {
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-rose-50 text-rose-800',
        'success' => 'bg-emerald-50 text-emerald-800',
        default => 'bg-brand-50 text-brand-800',
    };
    $classes = 'surface-stat'.($href ? ' group block transition hover:border-brand-300 hover:shadow-panel' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold tracking-wide text-ink-muted">{{ $label }}</div>
                <div class="mt-2 text-3xl font-bold tracking-tight text-ink">{{ $value }}</div>
                @if ($hint)
                    <div class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</div>
                @endif
            </div>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold {{ $toneClass }}">
                {{ $slot->isNotEmpty() ? $slot : '•' }}
            </span>
        </div>
@if ($href)
    </a>
@else
    </div>
@endif
