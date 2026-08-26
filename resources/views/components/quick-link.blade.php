@props([
    'href',
    'title',
    'description' => null,
])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge(['class' => 'group flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-soft transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card']) }}
>
    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-brand-700 transition duration-200 group-hover:border-brand-700 group-hover:bg-brand-700 group-hover:text-white">
        {{ $slot }}
    </span>
    <span class="min-w-0 flex-1 pt-0.5">
        <span class="block text-sm font-bold text-ink transition duration-200 group-hover:text-brand-900">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-5 text-ink-muted">{{ $description }}</span>
        @endif
    </span>
    <span class="mt-1.5 shrink-0 text-slate-300 transition duration-200 group-hover:text-brand-500" aria-hidden="true">
        <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </span>
</a>
