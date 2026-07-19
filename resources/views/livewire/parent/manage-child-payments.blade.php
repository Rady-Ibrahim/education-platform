<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section
        :title="'مدفوعات '.$student->name"
        :subtitle="'الكود: '.($student->student_code ?? '—').' — اشترك وأرسل إثبات فودافون كاش نيابة عنه.'"
    >
        <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wide text-ink-muted">خطط الاشتراك</h4>
            @forelse ($plans as $plan)
                <div class="list-row">
                    <div>
                        <div class="font-semibold text-ink">{{ $plan->name }}</div>
                        <div class="text-sm text-ink-muted">{{ $plan->subject->name }} — {{ $plan->teacher?->name }}</div>
                        <div class="text-sm text-ink-muted">{{ number_format($plan->price, 2) }} ج.م / {{ $plan->duration_days }} يوم</div>
                    </div>
                    <x-primary-button wire:click="enroll({{ $plan->id }})">اشترك للابن</x-primary-button>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد خطط. تأكد أن الابن منضم لمدرّس لديه خطط نشطة.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>

    <x-page-section title="الاشتراكات والدفع" subtitle="حالة كل اشتراك وإرسال إثبات فودافون عند الحاجة.">
        <div class="space-y-3">
            @forelse ($subscriptions as $subscription)
                @php $latestPayment = $subscription->payments->first(); @endphp
                <div class="list-row !items-stretch !flex-col space-y-3">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-semibold text-ink">{{ $subscription->plan->name }}</div>
                            <div class="text-sm text-ink-muted">{{ $subscription->subject->name }} — {{ $subscription->teacher->name }}</div>
                            <div class="text-sm">الحالة: <span class="font-medium">{{ $subscription->status->label() }}</span></div>
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
                            <x-secondary-button wire:click="startPayment({{ $subscription->id }})">
                                إرسال إثبات فودافون
                            </x-secondary-button>
                        @elseif ($latestPayment?->isPending())
                            <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">بانتظار مراجعة المدرس</span>
                        @endif
                    </div>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment && ! empty($instructions[$subscription->id]))
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-ink space-y-1">
                            <div class="font-medium">تعليمات التحويل</div>
                            @if ($instructions[$subscription->id]['vodafone_cash_number'])
                                <div>رقم فودافون كاش: <span class="font-mono font-semibold">{{ $instructions[$subscription->id]['vodafone_cash_number'] }}</span></div>
                            @else
                                <div class="text-amber-700">لم يُضبط رقم المحفظة بعد — تواصل مع المدرس أو السنتر.</div>
                            @endif
                            @if ($instructions[$subscription->id]['payment_instructions'])
                                <div class="whitespace-pre-line">{{ $instructions[$subscription->id]['payment_instructions'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($payingSubscriptionId === $subscription->id)
                        <div class="space-y-3 border-t border-slate-100 pt-4">
                            <div>
                                <x-input-label value="رقم عملية فودافون كاش" />
                                <x-text-input wire:model="externalReference" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('externalReference')" />
                            </div>
                            <div>
                                <x-input-label value="صورة وصل فودافون كاش (مطلوبة)" />
                                <input type="file" wire:model="proof" class="mt-1.5 block w-full text-sm" accept="image/*" />
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
