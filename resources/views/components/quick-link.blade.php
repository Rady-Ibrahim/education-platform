@props([
    'href',
    'title',
    'description' => null,
])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge(['class' => 'group flex items-start gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50/40']) }}
>
    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-800 transition group-hover:bg-brand-700 group-hover:text-white">
        {{ $slot }}
    </span>
    <span class="min-w-0">
        <span class="block text-sm font-bold text-ink">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-relaxed text-ink-muted">{{ $description }}</span>
        @endif
    </span>
</a>
