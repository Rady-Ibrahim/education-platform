@props([
    'href',
    'title',
    'description' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/40 p-3.5 transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:bg-white hover:shadow-panel']) }}
>
    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-brand-800 ring-1 ring-slate-200/80 transition group-hover:bg-brand-700 group-hover:text-white group-hover:ring-brand-700">
        {{ $slot }}
    </span>
    <span class="min-w-0 pt-0.5">
        <span class="block text-sm font-bold text-ink">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs leading-5 text-ink-muted">{{ $description }}</span>
        @endif
    </span>
</a>
