<div class="space-y-5">
    @if (session('parent_link_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('parent_link_status') }}
        </div>
    @endif

    <x-page-section title="طلبات ربط أولياء الأمور" subtitle="قبول الطلب يفعّل الربط؛ الرفض يغلقه.">
        <div class="space-y-3">
            @forelse ($pending as $link)
                <div class="list-row">
                    <div>
                        <div class="font-semibold text-ink">{{ $link->parent->name }}</div>
                        <div class="text-sm text-ink-muted">{{ $link->parent->email }}</div>
                        @if ($link->relationship)
                            <div class="text-sm text-ink-muted">{{ $link->relationship->label() }}</div>
                        @endif
                        @if ($link->message)
                            <div class="mt-1 text-sm text-ink-soft">{{ $link->message }}</div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button wire:click="approve({{ $link->id }})">قبول</x-primary-button>
                        <x-danger-button wire:click="reject({{ $link->id }})">رفض</x-danger-button>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد طلبات معلّقة.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>

    <x-page-section title="أولياء الأمور المرتبطون" subtitle="يمكنك إلغاء الربط في أي وقت.">
        <ul class="space-y-2">
            @forelse ($active as $link)
                <li class="list-row text-sm">
                    <span>
                        {{ $link->parent->name }} — {{ $link->parent->email }}
                        @if ($link->relationship)
                            ({{ $link->relationship->label() }})
                        @endif
                    </span>
                    <x-danger-button wire:click="revoke({{ $link->id }})" class="!text-xs">إلغاء الربط</x-danger-button>
                </li>
            @empty
                <li class="empty-state text-sm text-ink-muted">لا يوجد أولياء أمور مرتبطون بعد.</li>
            @endforelse
        </ul>
    </x-page-section>
</div>
