<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section
        :title="$student->name"
        :subtitle="($student->email ?? '').' — '.($student->student_code ?? '')"
    >
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="kpi-card">
                <div class="kpi-label">محصّل مؤكد</div>
                <div class="kpi-value">{{ number_format($confirmed_total, 0) }} <span class="text-sm font-semibold text-ink-muted">ج.م</span></div>
            </div>
            <div class="kpi-card-warn">
                <div class="text-[11px] font-semibold uppercase tracking-[0.06em] text-amber-800">معلّق للمراجعة</div>
                <div class="mt-1.5 text-2xl font-bold tracking-tight text-amber-950">{{ number_format($pending_total, 0) }} <span class="text-sm font-semibold">ج.م</span></div>
            </div>
        </div>
    </x-page-section>

    <x-page-section title="الاشتراكات" subtitle="إيقاف أو إعادة تفعيل اشتراك الطالب.">
        <div class="space-y-2">
            @forelse ($subscriptions as $subscription)
                <div class="list-row">
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
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد اشتراكات.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>

    <x-page-section title="سجل المدفوعات" subtitle="كل الدفعات المرتبطة بالطالب.">
        <div class="overflow-hidden rounded-xl border border-slate-200">
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
    </x-page-section>

    @if ($invoices->isNotEmpty())
        <x-page-section title="الفواتير">
            <ul class="space-y-2 text-sm">
                @foreach ($invoices as $invoice)
                    <li class="list-row">
                        <span class="font-mono">{{ $invoice->invoice_number }}</span>
                        <span class="text-ink-muted">{{ number_format($invoice->amount, 2) }} ج.م — {{ $invoice->issued_at->format('Y-m-d') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-page-section>
    @endif
</div>
