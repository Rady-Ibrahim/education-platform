@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'tone' => 'default',
])

@php
    $iconWrap = match ($tone) {
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        default => 'bg-brand-50 text-brand-800 ring-brand-100',
    };
    $classes = 'surface-stat'.($href ? ' group block transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-panel' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">{{ $label }}</div>
                <div class="mt-2 text-[1.75rem] font-bold leading-none tracking-tight text-ink sm:text-3xl">{{ $value }}</div>
                @if ($hint)
                    <div class="mt-2 text-xs text-ink-muted">{{ $hint }}</div>
                @endif
            </div>
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $iconWrap }}">
                @if ($slot->isNotEmpty())
                    {{ $slot }}
                @else
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M7 16V8m5 8V5m5 11v-6"/></svg>
                @endif
            </span>
        </div>
@if ($href)
    </a>
@else
    </div>
@endif
