@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'page-shell']) }}>
    <header class="page-header">
        <div class="min-w-0">
            @isset($eyebrow)
                <div class="mb-2.5">{{ $eyebrow }}</div>
            @endisset
            <h1 class="page-header-title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="page-header-sub">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </header>

    <div class="space-y-5">
        {{ $slot }}
    </div>
</div>
