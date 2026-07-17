<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = ! open"
        class="relative inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none"
        aria-label="الإشعارات"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute top-1 end-1 inline-flex items-center justify-center min-w-[1.1rem] h-4 px-1 text-[10px] font-semibold text-white bg-red-500 rounded-full">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute end-0 z-50 mt-2 w-80 bg-white rounded-md shadow-lg border border-gray-100 overflow-hidden"
    >
        <div class="flex items-center justify-between px-4 py-2 border-b bg-gray-50">
            <span class="text-sm font-medium text-gray-800">الإشعارات</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800">
                    تعليم الكل كمقروء
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y">
            @forelse ($notifications as $notification)
                <button
                    type="button"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="w-full text-start px-4 py-3 hover:bg-gray-50 {{ $notification->read_at ? 'opacity-70' : 'bg-indigo-50/40' }}"
                >
                    <div class="text-sm text-gray-800">
                        {{ $notification->data['message'] ?? 'إشعار' }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </button>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 text-center">لا توجد إشعارات.</div>
            @endforelse
        </div>
    </div>
</div>
