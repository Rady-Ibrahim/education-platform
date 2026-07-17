@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])

<section {{ $attributes->merge(['class' => 'surface-panel']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                @if ($title)
                    <h2 class="section-title">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="section-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['p-5 sm:p-6' => $padding])>
        {{ $slot }}
    </div>
</section>
