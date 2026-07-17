<div class="space-y-8">
    @if (session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="saveSettings" class="space-y-4 rounded-lg border bg-white p-4">
        <h3 class="font-medium">إعدادات اشتراك المنصة (المدرس → الإدارة)</h3>
        <p class="text-sm text-gray-500">فودافون كاش للمنصة + فترة مجانية ثم رسوم دورية.</p>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label value="رقم فودافون كاش الإدارة" />
                <x-text-input wire:model="vodafoneCashNumber" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="الرسوم (ج.م)" />
                <x-text-input wire:model="monthlyFee" type="number" step="0.01" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="أيام الفترة المجانية" />
                <x-text-input wire:model="trialDays" type="number" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="أيام فترة الاشتراك بعد الدفع" />
                <x-text-input wire:model="periodDays" type="number" class="mt-1 block w-full" />
            </div>
        </div>
        <div>
            <x-input-label value="تعليمات التحويل" />
            <textarea wire:model="paymentInstructions" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>
        <x-primary-button type="submit">حفظ الإعدادات</x-primary-button>
    </form>

    <div class="rounded-lg border bg-white p-4 space-y-4">
        <h3 class="font-medium">مدفوعات المنصة بانتظار المراجعة</h3>
        @forelse ($pendingPayments as $payment)
            <div class="border rounded-md p-3 space-y-2" wire:key="pp-{{ $payment->id }}">
                <div class="flex flex-wrap justify-between gap-2 text-sm">
                    <div>
                        <div class="font-medium">{{ $payment->teacher?->name }}</div>
                        <div class="text-gray-600">{{ number_format((float) $payment->amount, 2) }} ج.م — {{ $payment->external_reference }}</div>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                        <x-secondary-button wire:click="startReject({{ $payment->id }})">رفض</x-secondary-button>
                    </div>
                </div>
                @if ($rejectingPaymentId === $payment->id)
                    <div class="space-y-2">
                        <x-text-input wire:model="rejectionReason" class="block w-full" placeholder="سبب الرفض" />
                        <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">لا توجد مدفوعات معلّقة.</p>
        @endforelse
        {{ $pendingPayments->links() }}
    </div>
</div>
