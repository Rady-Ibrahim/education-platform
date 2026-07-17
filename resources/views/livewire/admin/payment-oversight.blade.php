<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($payments as $payment)
            <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-medium">{{ $payment->student->name }}</div>
                    <div class="text-sm text-gray-600">
                        المدرس: {{ $payment->teacher?->name ?? '—' }}
                        — {{ $payment->channel->label() }}
                        — {{ number_format($payment->amount, 2) }} ج.م
                    </div>
                    @if ($payment->external_reference)
                        <div class="text-sm text-gray-500">رقم العملية: {{ $payment->external_reference }}</div>
                    @endif
                </div>
                <div class="flex gap-2">
                    <x-primary-button wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                    <x-danger-button wire:click="startReject({{ $payment->id }})">رفض</x-danger-button>
                </div>
            </div>

            @if ($rejectingPaymentId === $payment->id)
                <div class="border rounded-lg p-4 bg-red-50 space-y-2">
                    <x-text-input wire:model="rejectionReason" placeholder="سبب الرفض" class="block w-full" />
                    <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                </div>
            @endif
        @empty
            <p class="text-gray-600">لا توجد مدفوعات بانتظار المراجعة.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</div>
