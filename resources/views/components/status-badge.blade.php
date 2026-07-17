@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-100',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'brand' => 'bg-brand-50 text-brand-800 ring-brand-100',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
