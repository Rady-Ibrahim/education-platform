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
        'warning' => 'border-amber-200/90 bg-amber-50/50 hover:border-amber-300 hover:bg-amber-50',
        'success' => 'border-emerald-200/90 bg-emerald-50/40 hover:border-emerald-300',
        'accent' => 'border-accent/30 bg-[#FFF8E8] hover:border-accent/60',
        default => 'border-slate-200/90 bg-white hover:border-brand-300 hover:bg-brand-50/40',
    };
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => "group relative flex h-full items-start gap-3 rounded-2xl border p-4 shadow-soft transition duration-200 hover:-translate-y-0.5 hover:shadow-panel {$toneClass}"]) }}
>
    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-950 text-xs font-bold text-white transition group-hover:bg-brand-700">
        {{ $step }}
    </span>
    <span class="min-w-0 flex-1 pt-0.5">
        <span class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-bold text-ink">{{ $title }}</span>
            @if ($badge !== null && $badge !== '' && (int) $badge > 0)
                <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-amber-950">{{ $badge }}</span>
            @endif
        </span>
        @if ($description)
            <span class="mt-1 block text-xs leading-5 text-ink-muted">{{ $description }}</span>
        @endif
    </span>
    <span class="mt-1 text-slate-300 transition group-hover:text-brand-600" aria-hidden="true">
        <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </span>
</a>
