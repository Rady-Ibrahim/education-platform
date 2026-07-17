<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold">{{ $student->name }}</h3>
            <p class="text-sm text-gray-500">{{ $student->email }} — {{ $student->student_code }}</p>
        </div>
        <a href="{{ route('teacher.students') }}" class="text-sm text-indigo-600 hover:text-indigo-800" wire:navigate>رجوع للطلاب</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="border rounded-lg p-4 bg-green-50">
            <div class="text-sm text-green-700">محصّل مؤكد</div>
            <div class="text-2xl font-semibold">{{ number_format($confirmed_total, 2) }} ج.م</div>
        </div>
        <div class="border rounded-lg p-4 bg-amber-50">
            <div class="text-sm text-amber-700">معلّق للمراجعة</div>
            <div class="text-2xl font-semibold">{{ number_format($pending_total, 2) }} ج.م</div>
        </div>
    </div>

    <div>
        <h4 class="font-medium mb-3">الاشتراكات</h4>
        <div class="space-y-2">
            @forelse ($subscriptions as $subscription)
                <div class="border rounded-md px-3 py-2 text-sm flex justify-between gap-3">
                    <span>{{ $subscription->plan?->name }} — {{ $subscription->subject?->name }}</span>
                    <span class="font-medium">{{ $subscription->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد اشتراكات.</p>
            @endforelse
        </div>
    </div>

    <div>
        <h4 class="font-medium mb-3">سجل المدفوعات</h4>
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-3 py-2">التاريخ</th>
                        <th class="text-start px-3 py-2">القناة</th>
                        <th class="text-start px-3 py-2">المبلغ</th>
                        <th class="text-start px-3 py-2">الحالة</th>
                        <th class="text-start px-3 py-2">المرجع</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-3 py-2">{{ $payment->created_at->format('Y-m-d') }}</td>
                            <td class="px-3 py-2">{{ $payment->channel->label() }}</td>
                            <td class="px-3 py-2">{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-3 py-2">{{ $payment->status->label() }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $payment->external_reference ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">لا توجد مدفوعات.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($invoices->isNotEmpty())
        <div>
            <h4 class="font-medium mb-3">الفواتير</h4>
            <ul class="space-y-2 text-sm">
                @foreach ($invoices as $invoice)
                    <li class="border rounded-md px-3 py-2 flex justify-between">
                        <span class="font-mono">{{ $invoice->invoice_number }}</span>
                        <span>{{ number_format($invoice->amount, 2) }} ج.م — {{ $invoice->issued_at->format('Y-m-d') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
