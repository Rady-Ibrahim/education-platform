<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-bold text-ink">{{ $student->name }}</h3>
            <p class="text-sm text-ink-muted">{{ $student->email }} — {{ $student->student_code }}</p>
        </div>
        <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>رجوع للطلاب</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="surface-stat">
            <div class="ps-3">
                <div class="text-sm text-ink-muted">محصّل مؤكد</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($confirmed_total, 2) }} ج.م</div>
            </div>
        </div>
        <div class="surface-stat">
            <div class="ps-3">
                <div class="text-sm text-ink-muted">معلّق للمراجعة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($pending_total, 2) }} ج.م</div>
            </div>
        </div>
    </div>

    <div>
        <h4 class="mb-3 font-bold text-ink">الاشتراكات</h4>
        <div class="space-y-2">
            @forelse ($subscriptions as $subscription)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-semibold text-ink">{{ $subscription->plan?->name }} — {{ $subscription->subject?->name }}</div>
                        <div class="mt-1 text-xs text-ink-muted">
                            @if ($subscription->ends_at)
                                ينتهي: {{ $subscription->ends_at->format('Y-m-d') }}
                            @else
                                بدون تاريخ انتهاء
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($subscription->status === $activeStatus)
                            <x-status-badge tone="success">{{ $subscription->status->label() }}</x-status-badge>
                            <x-danger-button type="button" wire:click="suspendSubscription({{ $subscription->id }})" wire:confirm="إيقاف هذا الاشتراك؟">إيقاف</x-danger-button>
                        @elseif ($subscription->status === $suspendedStatus)
                            <x-status-badge tone="danger">{{ $subscription->status->label() }}</x-status-badge>
                            <x-primary-button type="button" wire:click="reactivateSubscription({{ $subscription->id }})">إعادة تفعيل</x-primary-button>
                        @else
                            <x-status-badge tone="neutral">{{ $subscription->status->label() }}</x-status-badge>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-muted">لا توجد اشتراكات.</p>
            @endforelse
        </div>
    </div>

    <div>
        <h4 class="mb-3 font-bold text-ink">سجل المدفوعات</h4>
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>القناة</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>المرجع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('Y-m-d') }}</td>
                            <td>{{ $payment->channel->label() }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->status->label() }}</td>
                            <td class="font-mono text-xs">{{ $payment->external_reference ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-ink-muted">لا توجد مدفوعات.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($invoices->isNotEmpty())
        <div>
            <h4 class="mb-3 font-bold text-ink">الفواتير</h4>
            <ul class="space-y-2 text-sm">
                @foreach ($invoices as $invoice)
                    <li class="flex justify-between rounded-xl border border-slate-200 px-3 py-2">
                        <span class="font-mono">{{ $invoice->invoice_number }}</span>
                        <span>{{ number_format($invoice->amount, 2) }} ج.م — {{ $invoice->issued_at->format('Y-m-d') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
