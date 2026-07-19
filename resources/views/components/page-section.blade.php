@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'page-section']) }}>
    @if ($title || isset($actions) || $subtitle)
        <div class="page-section-head">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="page-section-title">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="page-section-sub">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['mt-4' => $title || $subtitle || isset($actions)])>
        {{ $slot }}
    </div>
</section>
