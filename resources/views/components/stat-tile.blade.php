@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'tone' => 'default',
])

@php
    $toneClasses = match ($tone) {
        'warning' => ['wrap' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100', 'dot' => 'bg-amber-400'],
        'danger'  => ['wrap' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100', 'dot' => 'bg-rose-400'],
        'success' => ['wrap' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100', 'dot' => 'bg-emerald-400'],
        default   => ['wrap' => 'bg-brand-50 text-brand-800 ring-1 ring-brand-100', 'dot' => 'bg-brand-400'],
    };
    $classes = 'surface-stat'.($href ? ' group block transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card-hover' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">{{ $label }}</div>
                </div>
                <div class="mt-2.5 text-[1.75rem] font-bold leading-none tracking-tight text-ink sm:text-3xl">{{ $value }}</div>
                @if ($hint)
                    <div class="mt-2 text-xs text-ink-muted">{{ $hint }}</div>
                @endif
            </div>
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition duration-200 {{ $toneClasses['wrap'] }} {{ $href ? 'group-hover:scale-105' : '' }}">
                @if ($slot->isNotEmpty())
                    {{ $slot }}
                @else
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M7 16V8m5 8V5m5 11v-6"/></svg>
                @endif
            </span>
        </div>
        @if ($href)
            <div class="mt-3 flex items-center gap-1 text-[11px] font-semibold text-brand-700 opacity-0 transition duration-200 group-hover:opacity-100">
                عرض التفاصيل
                <svg class="h-3 w-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        @endif
@if ($href)
    </a>
@else
    </div>
@endif
