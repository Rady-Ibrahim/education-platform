@props([
    'step',
    'title',
    'description' => null,
    'href',
    'tone' => 'default',
    'badge' => null,
])

@php
    $toneClass = match ($tone) {
        'warning' => 'border-amber-200 bg-amber-50/80 hover:border-amber-300',
        'success' => 'border-emerald-200 bg-emerald-50/70 hover:border-emerald-300',
        'accent' => 'border-accent/40 bg-accent-soft/40 hover:border-accent',
        default => 'border-slate-200/90 bg-white hover:border-brand-300 hover:bg-brand-50/30',
    };
@endphp

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge(['class' => "group relative flex items-start gap-3 rounded-2xl border p-4 shadow-soft transition {$toneClass}"]) }}
>
    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-sm font-bold text-white shadow-soft transition group-hover:bg-brand-800">
        {{ $step }}
    </span>
    <span class="min-w-0 flex-1">
        <span class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-bold text-ink">{{ $title }}</span>
            @if ($badge !== null && $badge !== '' && (int) $badge > 0)
                <span class="rounded-lg bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900">{{ $badge }}</span>
            @endif
        </span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-relaxed text-ink-muted">{{ $description }}</span>
        @endif
    </span>
    <span class="mt-1 text-ink-muted transition group-hover:text-brand-700" aria-hidden="true">
        <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </span>
</a>
