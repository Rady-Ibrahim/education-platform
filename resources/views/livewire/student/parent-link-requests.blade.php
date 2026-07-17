<div>
    @if (session('parent_link_status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('parent_link_status') }}
        </div>
    @endif

    <div class="space-y-6">
        <div>
            <h4 class="font-medium mb-3">طلبات ربط أولياء الأمور</h4>
            <div class="space-y-3">
                @forelse ($pending as $link)
                    <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-medium">{{ $link->parent->name }}</div>
                            <div class="text-sm text-gray-600">{{ $link->parent->email }}</div>
                            @if ($link->relationship)
                                <div class="text-sm text-gray-500">{{ $link->relationship->label() }}</div>
                            @endif
                            @if ($link->message)
                                <div class="text-sm text-gray-500 mt-1">{{ $link->message }}</div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button wire:click="approve({{ $link->id }})">قبول</x-primary-button>
                            <x-danger-button wire:click="reject({{ $link->id }})">رفض</x-danger-button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">لا توجد طلبات معلّقة.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h4 class="font-medium mb-3">أولياء الأمور المرتبطون</h4>
            <ul class="space-y-2">
                @forelse ($active as $link)
                    <li class="border rounded-md px-3 py-2 flex items-center justify-between text-sm">
                        <span>
                            {{ $link->parent->name }} — {{ $link->parent->email }}
                            @if ($link->relationship)
                                ({{ $link->relationship->label() }})
                            @endif
                        </span>
                        <x-danger-button wire:click="revoke({{ $link->id }})" class="!text-xs">إلغاء الربط</x-danger-button>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">لا يوجد أولياء أمور مرتبطون بعد.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
