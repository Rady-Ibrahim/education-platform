<div class="space-y-4">
    @forelse ($items as $message)
        <article
            wire:click="markRead({{ $message->id }})"
            @class([
                'cursor-pointer rounded-2xl border p-4 transition',
                'border-brand-200 bg-brand-50/40' => $message->isUnread(),
                'border-slate-200 bg-white' => ! $message->isUnread(),
            ])
        >
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="font-bold text-ink">{{ $message->teacher?->name }}</div>
                    <div class="text-xs text-ink-muted">{{ $message->teacher?->headline ?: 'مدرس' }}</div>
                    @if ($message->student)
                        <div class="mt-1 text-xs font-medium text-brand-800">بخصوص: {{ $message->student->name }}</div>
                    @endif
                </div>
                <div class="text-xs text-ink-muted">
                    {{ $message->created_at?->diffForHumans() }}
                    @if ($message->isUnread())
                        <span class="ms-2 rounded-md bg-brand-700 px-1.5 py-0.5 text-[10px] font-bold text-white">جديد</span>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-sm leading-relaxed text-ink whitespace-pre-line">{{ $message->body }}</p>
            @if ($message->imageUrl())
                <a href="{{ $message->imageUrl() }}" target="_blank" class="mt-3 block overflow-hidden rounded-xl border border-slate-200">
                    <img src="{{ $message->imageUrl() }}" alt="مرفق" class="max-h-72 w-full object-contain bg-slate-50">
                </a>
            @endif
        </article>
    @empty
        <p class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-ink-muted">
            لا توجد رسائل من المدرسين بعد.
        </p>
    @endforelse

    <div>{{ $items->links() }}</div>
</div>
