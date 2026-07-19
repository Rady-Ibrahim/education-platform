<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <x-page-section title="اشتراك المنصة (سنتر)" subtitle="الفترة المجانية ثم رسوم المنصة لإدارة السنتر.">
        <div class="space-y-2 text-sm">
            <div>الحالة: <span class="font-semibold">{{ $subscription->status->label() }}</span></div>
            @if ($subscription->trial_ends_at)
                <div class="text-ink-muted">نهاية التجربة: {{ $subscription->trial_ends_at->format('Y-m-d') }}</div>
            @endif
            @if ($subscription->current_period_ends_at)
                <div class="text-ink-muted">الاشتراك الحالي حتى: {{ $subscription->current_period_ends_at->format('Y-m-d') }}</div>
            @endif
            <div>الرسوم: <span class="font-semibold">{{ number_format((float) ($subscription->amount ?? $settings->monthly_fee), 2) }} ج.م</span> / {{ $settings->period_days }} يوم</div>
            <p class="text-ink-muted">الفترة المجانية {{ $settings->trial_days }} يوم من تاريخ التسجيل.</p>
        </div>
    </x-page-section>

    <x-page-section title="فودافون كاش الإدارة" subtitle="حوّل الرسوم ثم ارفع صورة الوصل.">
        @if ($settings->vodafone_cash_number)
            <div class="mb-3 font-mono text-lg font-bold text-ink">{{ $settings->vodafone_cash_number }}</div>
        @else
            <p class="mb-3 text-sm text-amber-800">الإدارة لم تضبط رقم المحفظة بعد.</p>
        @endif
        @if ($settings->payment_instructions)
            <p class="mb-4 text-sm whitespace-pre-line text-ink-muted">{{ $settings->payment_instructions }}</p>
        @endif

        <form wire:submit="submit" class="space-y-3">
            <div>
                <x-input-label value="رقم العملية" />
                <x-text-input wire:model="externalReference" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('externalReference')" />
            </div>
            <div>
                <x-input-label value="صورة وصل فودافون كاش (مطلوبة)" />
                <input type="file" wire:model="proof" accept="image/*" class="mt-1.5 block w-full text-sm">
                <x-input-error :messages="$errors->get('proof')" />
            </div>
            <x-primary-button type="submit">إرسال إثبات دفع المنصة</x-primary-button>
        </form>
    </x-page-section>

    <x-page-section title="سجل المدفوعات">
        <ul class="space-y-2 text-sm">
            @forelse ($payments as $payment)
                <li class="list-row">
                    <span>{{ number_format((float) $payment->amount, 2) }} ج.م — {{ $payment->external_reference }}</span>
                    <span class="text-ink-muted">{{ $payment->status->label() }}</span>
                </li>
            @empty
                <li class="empty-state text-sm text-ink-muted">لا مدفوعات بعد.</li>
            @endforelse
        </ul>
    </x-page-section>
</div>
