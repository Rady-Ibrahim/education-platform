<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="طلبات الانتظار" subtitle="قبول الطالب يضيفه لمكتبك؛ الرفض يغلق الطلب.">
        <div class="space-y-3">
            @forelse ($requests as $joinRequest)
                <div class="list-row">
                    <div class="min-w-0">
                        <div class="font-bold text-ink">{{ $joinRequest->student->name }}</div>
                        <div class="text-sm text-ink-muted">{{ $joinRequest->student->email }}</div>
                        @if ($joinRequest->message)
                            <div class="mt-1 text-sm text-ink-soft">{{ $joinRequest->message }}</div>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-primary-button wire:click="approve({{ $joinRequest->id }})">قبول</x-primary-button>
                        <x-danger-button wire:click="reject({{ $joinRequest->id }})">رفض</x-danger-button>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد طلبات انضمام حاليًا.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $requests->links() }}</div>
    </x-page-section>
</div>
