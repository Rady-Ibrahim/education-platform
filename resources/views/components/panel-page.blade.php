@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'page-shell']) }}>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            @isset($eyebrow)
                <div class="mb-2">{{ $eyebrow }}</div>
            @endisset
            <h1 class="text-2xl font-bold tracking-tight text-brand-950 sm:text-[1.85rem]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
