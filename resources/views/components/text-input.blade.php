@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-brand-200 bg-white text-ink shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:opacity-60']) }}>
