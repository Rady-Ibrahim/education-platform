<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-lg border bg-white p-4 space-y-2">
        <h3 class="font-medium text-gray-900">اشتراك المنصة (سنتر)</h3>
        <p class="text-sm text-gray-600">الفترة المجانية {{ $settings->trial_days }} يوم من تاريخ التسجيل، بعدها تدفع فودافون كاش لإدارة المنصة.</p>
        <div class="text-sm">
            الحالة: <span class="font-semibold">{{ $subscription->status->label() }}</span>
        </div>
        @if ($subscription->trial_ends_at)
            <div class="text-sm text-gray-600">نهاية التجربة: {{ $subscription->trial_ends_at->format('Y-m-d') }}</div>
        @endif
        @if ($subscription->current_period_ends_at)
            <div class="text-sm text-gray-600">الاشتراك الحالي حتى: {{ $subscription->current_period_ends_at->format('Y-m-d') }}</div>
        @endif
        <div class="text-sm">الرسوم: <span class="font-semibold">{{ number_format((float) ($subscription->amount ?? $settings->monthly_fee), 2) }} ج.م</span> / {{ $settings->period_days }} يوم</div>
    </div>

    <div class="rounded-lg border border-brand-200 bg-brand-50/50 p-4 space-y-3">
        <h4 class="font-medium text-brand-950">فودافون كاش الإدارة</h4>
        @if ($settings->vodafone_cash_number)
            <div class="font-mono text-lg">{{ $settings->vodafone_cash_number }}</div>
        @else
            <p class="text-sm text-amber-800">الإدارة لم تضبط رقم المحفظة بعد.</p>
        @endif
        @if ($settings->payment_instructions)
            <p class="text-sm whitespace-pre-line text-brand-900/80">{{ $settings->payment_instructions }}</p>
        @endif

        <form wire:submit="submit" class="space-y-3 pt-2">
            <div>
                <x-input-label value="رقم العملية" />
                <x-text-input wire:model="externalReference" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('externalReference')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="صورة وصل فودافون كاش (مطلوبة)" />
                <input type="file" wire:model="proof" accept="image/*" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('proof')" class="mt-1" />
            </div>
            <x-primary-button type="submit">إرسال إثبات دفع المنصة</x-primary-button>
        </form>
    </div>

    <div>
        <h4 class="font-medium mb-2">سجل المدفوعات</h4>
        <ul class="space-y-2 text-sm">
            @forelse ($payments as $payment)
                <li class="border rounded-md px-3 py-2 flex justify-between gap-2">
                    <span>{{ number_format((float) $payment->amount, 2) }} ج.م — {{ $payment->external_reference }}</span>
                    <span>{{ $payment->status->label() }}</span>
                </li>
            @empty
                <li class="text-gray-500">لا مدفوعات بعد.</li>
            @endforelse
        </ul>
    </div>
</div>
