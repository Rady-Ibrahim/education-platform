<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <x-page-section title="إعدادات اشتراك المنصة" subtitle="فودافون كاش للمنصة + فترة مجانية ثم رسوم دورية.">
        <form wire:submit="saveSettings" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label value="رقم فودافون كاش الإدارة" />
                    <x-text-input wire:model="vodafoneCashNumber" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label value="الرسوم (ج.م)" />
                    <x-text-input wire:model="monthlyFee" type="number" step="0.01" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label value="أيام الفترة المجانية" />
                    <x-text-input wire:model="trialDays" type="number" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label value="أيام فترة الاشتراك بعد الدفع" />
                    <x-text-input wire:model="periodDays" type="number" class="mt-1.5 block w-full" />
                </div>
            </div>
            <div>
                <x-input-label value="تعليمات التحويل" />
                <textarea wire:model="paymentInstructions" rows="3" class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm"></textarea>
            </div>
            <x-primary-button type="submit">حفظ الإعدادات</x-primary-button>
        </form>
    </x-page-section>

    <x-page-section title="مدفوعات المنصة بانتظار المراجعة" subtitle="تأكيد أو رفض إثباتات المدرسين.">
        <div class="space-y-3">
            @forelse ($pendingPayments as $payment)
                <div class="list-row !items-stretch !flex-col space-y-2" wire:key="pp-{{ $payment->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <div class="font-semibold text-ink">{{ $payment->teacher?->name }}</div>
                            <div class="text-ink-muted">{{ number_format((float) $payment->amount, 2) }} ج.م — {{ $payment->external_reference }}</div>
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                            <x-secondary-button wire:click="startReject({{ $payment->id }})">رفض</x-secondary-button>
                        </div>
                    </div>
                    @if ($rejectingPaymentId === $payment->id)
                        <div class="space-y-2 rounded-xl border border-rose-200 bg-rose-50 p-3">
                            <x-text-input wire:model="rejectionReason" class="block w-full" placeholder="سبب الرفض" />
                            <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد مدفوعات معلّقة.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-4">{{ $pendingPayments->links() }}</div>
    </x-page-section>
</div>
