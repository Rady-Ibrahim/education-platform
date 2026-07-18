<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($requests as $joinRequest)
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:flex-row sm:items-center sm:justify-between">
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
            <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-ink-muted">
                لا توجد طلبات انضمام حاليًا.
            </p>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
