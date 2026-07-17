<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = ! open"
        class="relative inline-flex items-center justify-center rounded-xl p-2 text-ink-muted transition hover:bg-brand-50 hover:text-brand-800 focus:outline-none"
        aria-label="الإشعارات"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute end-1 top-1 inline-flex h-4 min-w-[1.1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute end-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-panel"
    >
        <div class="flex items-center justify-between border-b border-brand-100 bg-brand-50/60 px-4 py-2.5">
            <span class="text-sm font-semibold text-ink">الإشعارات</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs font-medium text-brand-700 hover:text-brand-900">
                    تعليم الكل كمقروء
                </button>
            @endif
        </div>

        <div class="max-h-80 divide-y divide-brand-50 overflow-y-auto">
            @forelse ($notifications as $notification)
                <button
                    type="button"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="w-full px-4 py-3 text-start transition hover:bg-brand-50/70 {{ $notification->read_at ? 'opacity-70' : 'bg-brand-50/40' }}"
                >
                    <div class="text-sm text-ink">
                        {{ $notification->data['message'] ?? 'إشعار' }}
                    </div>
                    <div class="mt-1 text-xs text-ink-muted">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-ink-muted">لا توجد إشعارات.</div>
            @endforelse
        </div>
    </div>
</div>
