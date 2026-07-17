<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($pendingUsers as $pendingUser)
            <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-medium text-gray-900">{{ $pendingUser->name }}</div>
                    <div class="text-sm text-gray-600">{{ $pendingUser->email }} @if($pendingUser->phone) — {{ $pendingUser->phone }} @endif</div>
                    <div class="text-sm text-gray-500">
                        الدور:
                        {{ $pendingUser->primaryRole()?->label() ?? '—' }}
                        — منذ {{ $pendingUser->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="flex gap-2">
                    <x-primary-button wire:click="approve({{ $pendingUser->id }})" wire:loading.attr="disabled">
                        موافقة
                    </x-primary-button>
                    <x-danger-button wire:click="startReject({{ $pendingUser->id }})" wire:loading.attr="disabled">
                        رفض
                    </x-danger-button>
                </div>
            </div>

            @if ($rejectingUserId === $pendingUser->id)
                <div class="border rounded-lg p-4 bg-red-50 space-y-3">
                    <x-input-label value="سبب الرفض" />
                    <textarea wire:model="rejectionReason" class="w-full border-gray-300 rounded-md shadow-sm" rows="3"></textarea>
                    <x-input-error :messages="$errors->get('rejectionReason')" />
                    <div class="flex gap-2">
                        <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                        <x-secondary-button wire:click="$set('rejectingUserId', null)">إلغاء</x-secondary-button>
                    </div>
                </div>
            @endif
        @empty
            <p class="text-gray-600">لا توجد طلبات بانتظار الموافقة.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $pendingUsers->links() }}
    </div>
</div>
