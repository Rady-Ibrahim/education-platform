@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'page-shell']) }}>
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/70 pb-5">
        <div class="min-w-0">
            @isset($eyebrow)
                <div class="mb-2.5">{{ $eyebrow }}</div>
            @endisset
            <h1 class="text-2xl font-bold tracking-tight text-brand-950 sm:text-[1.9rem]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 max-w-2xl text-sm leading-6 text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </header>

    <div class="space-y-7">
        {{ $slot }}
    </div>
</div>
