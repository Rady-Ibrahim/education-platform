<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="طابور المدفوعات المعلّقة" subtitle="يشمل ما أكّده المدرسون وما ينتظر المراجعة.">
        <div class="space-y-3">
            @forelse ($payments as $payment)
                <div class="list-row !items-stretch !flex-col space-y-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-semibold text-ink">{{ $payment->student->name }}</div>
                            <div class="text-sm text-ink-muted">
                                المدرس: {{ $payment->teacher?->name ?? '—' }}
                                — {{ $payment->channel->label() }}
                                — {{ number_format($payment->amount, 2) }} ج.م
                            </div>
                            @if ($payment->external_reference)
                                <div class="text-sm text-ink-muted">رقم العملية: {{ $payment->external_reference }}</div>
                            @endif
                            @if ($payment->proof_path)
                                <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-700">عرض الإثبات ↗</a>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                            <x-danger-button wire:click="startReject({{ $payment->id }})">رفض</x-danger-button>
                        </div>
                    </div>

                    @if ($rejectingPaymentId === $payment->id)
                        <div class="space-y-2 rounded-xl border border-rose-200 bg-rose-50 p-3">
                            <x-text-input wire:model="rejectionReason" placeholder="سبب الرفض" class="block w-full" />
                            <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد مدفوعات بانتظار المراجعة.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $payments->links() }}</div>
    </x-page-section>
</div>
