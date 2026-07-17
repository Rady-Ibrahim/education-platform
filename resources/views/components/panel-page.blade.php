@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'page-shell']) }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-brand-950 sm:text-3xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 max-w-2xl text-sm text-ink-muted sm:text-base">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                {{ $actions }}
            </div>
        @endisset
    </div>

    {{ $slot }}
</div>
