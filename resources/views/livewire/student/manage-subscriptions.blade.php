<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="attention-strip">
        الدفع للمدرس:
        <strong>كاش في السنتر</strong> (المدرس يسجّله)، أو
        <strong>فودافون كاش من ولي الأمر</strong> لرقم المدرس.
        @unless ($studentVodafoneEnabled)
            حساب الطالب لا يرسل إثبات فودافون.
        @endunless
    </div>

    <x-page-section title="خطط الاشتراك المتاحة" subtitle="اختر خطة مدرسك للاشتراك.">
        <div class="space-y-3">
            @forelse ($plans as $plan)
                <div class="list-row">
                    <div>
                        <div class="font-semibold text-ink">{{ $plan->name }}</div>
                        <div class="text-sm text-ink-muted">
                            {{ $plan->subject->grade?->stage?->name }} / {{ $plan->subject->grade?->name }} / {{ $plan->subject->name }}
                        </div>
                        <div class="text-sm text-ink-muted">
                            المدرس: {{ $plan->teacher?->name }} — {{ number_format($plan->price, 2) }} ج.م / {{ $plan->duration_days }} يوم
                        </div>
                    </div>
                    <x-primary-button wire:click="enroll({{ $plan->id }})">اشترك</x-primary-button>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد خطط متاحة. انضم لمدرس أولاً من لوحة الطالب.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>

    <x-page-section title="اشتراكاتك" subtitle="حالة الاشتراك وإرسال إثبات فودافون إن لزم.">
        <div class="space-y-3">
            @forelse ($subscriptions as $subscription)
                @php $latestPayment = $subscription->payments->first(); @endphp
                <div class="list-row !items-stretch !flex-col space-y-3">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-semibold text-ink">{{ $subscription->plan->name }}</div>
                            <div class="text-sm text-ink-muted">{{ $subscription->subject->name }} — {{ $subscription->teacher->name }}</div>
                            <div class="text-sm">
                                الحالة: <span class="font-medium">{{ $subscription->status->label() }}</span>
                                @if ($subscription->ends_at)
                                    — ينتهي {{ $subscription->ends_at->format('Y-m-d') }}
                                @endif
                            </div>
                            @if ($latestPayment)
                                <div class="mt-1 text-sm text-ink-muted">
                                    آخر دفعة: {{ $latestPayment->status->label() }}
                                    @if ($latestPayment->rejection_reason)
                                        — {{ $latestPayment->rejection_reason }}
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if ($canSubmit[$subscription->id] ?? false)
                            <x-secondary-button wire:click="startPayment({{ $subscription->id }})">إرسال إثبات فودافون</x-secondary-button>
                        @elseif ($latestPayment?->isPending())
                            <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">بانتظار المراجعة</span>
                        @elseif ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment)
                            <span class="text-sm text-ink-muted">انتظر تسجيل الكاش من المدرس أو دفع ولي الأمر</span>
                        @endif
                    </div>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment && ! empty($instructions[$subscription->id]))
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-ink space-y-1">
                            <div class="font-medium">بيانات التحويل لولي الأمر (فودافون كاش المدرس)</div>
                            @if ($instructions[$subscription->id]['vodafone_cash_number'])
                                <div>رقم المحفظة: <span class="font-mono font-semibold">{{ $instructions[$subscription->id]['vodafone_cash_number'] }}</span></div>
                            @else
                                <div class="text-amber-700">المدرس لم يضبط رقم المحفظة بعد.</div>
                            @endif
                            @if ($instructions[$subscription->id]['payment_instructions'])
                                <div class="whitespace-pre-line">{{ $instructions[$subscription->id]['payment_instructions'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($studentVodafoneEnabled && $payingSubscriptionId === $subscription->id)
                        <div class="space-y-3 border-t border-slate-100 pt-4">
                            <div>
                                <x-input-label for="externalReference" value="رقم العملية" />
                                <x-text-input wire:model="externalReference" id="externalReference" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('externalReference')" />
                            </div>
                            <div>
                                <x-input-label for="proof" value="صورة وصل فودافون كاش (مطلوبة)" />
                                <input type="file" wire:model="proof" id="proof" class="mt-1.5 block w-full text-sm" accept="image/*" />
                                <x-input-error :messages="$errors->get('proof')" />
                            </div>
                            <x-primary-button wire:click="submitProof">إرسال للمراجعة</x-primary-button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد اشتراكات بعد.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>
</div>
