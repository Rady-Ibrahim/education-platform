<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 rounded-md border border-brand-200 bg-brand-50/60 p-3 text-sm text-brand-950">
        الدفع للمدرس:
        <strong>كاش في السنتر</strong> (المدرس يسجّله)، أو
        <strong>فودافون كاش من ولي الأمر</strong> لرقم المدرس.
        @unless ($studentVodafoneEnabled)
            حساب الطالب لا يرسل إثبات فودافون.
        @endunless
    </div>

    <div class="space-y-6">
        <div>
            <h4 class="font-medium mb-3">خطط الاشتراك المتاحة</h4>
            <div class="space-y-3">
                @forelse ($plans as $plan)
                    <div class="border rounded-lg p-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-sm text-gray-600">
                                {{ $plan->subject->grade?->stage?->name }} / {{ $plan->subject->grade?->name }} / {{ $plan->subject->name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                المدرس: {{ $plan->teacher?->name }} — {{ number_format($plan->price, 2) }} ج.م / {{ $plan->duration_days }} يوم
                            </div>
                        </div>
                        <x-primary-button wire:click="enroll({{ $plan->id }})">اشترك</x-primary-button>
                    </div>
                @empty
                    <p class="text-gray-600">لا توجد خطط متاحة. انضم لمدرس أولاً من لوحة الطالب.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h4 class="font-medium mb-3">اشتراكاتك</h4>
            <div class="space-y-3">
                @forelse ($subscriptions as $subscription)
                    @php $latestPayment = $subscription->payments->first(); @endphp
                    <div class="border rounded-lg p-4 space-y-3">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-medium">{{ $subscription->plan->name }}</div>
                                <div class="text-sm text-gray-600">{{ $subscription->subject->name }} — {{ $subscription->teacher->name }}</div>
                                <div class="text-sm">
                                    الحالة: <span class="font-medium">{{ $subscription->status->label() }}</span>
                                    @if ($subscription->ends_at)
                                        — ينتهي {{ $subscription->ends_at->format('Y-m-d') }}
                                    @endif
                                </div>
                                @if ($latestPayment)
                                    <div class="text-sm text-gray-500 mt-1">
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
                                <span class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">بانتظار المراجعة</span>
                            @elseif ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment)
                                <span class="text-sm text-slate-600">انتظر تسجيل الكاش من المدرس أو دفع ولي الأمر</span>
                            @endif
                        </div>

                        @if ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment && ! empty($instructions[$subscription->id]))
                            <div class="bg-slate-50 border rounded-md p-3 text-sm text-gray-700 space-y-1">
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
                            <div class="border-t pt-4 space-y-3">
                                <div>
                                    <x-input-label for="externalReference" value="رقم العملية" />
                                    <x-text-input wire:model="externalReference" id="externalReference" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('externalReference')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="proof" value="صورة الإثبات (اختياري)" />
                                    <input type="file" wire:model="proof" id="proof" class="block mt-1 w-full text-sm" accept="image/*" />
                                    <x-input-error :messages="$errors->get('proof')" class="mt-1" />
                                </div>
                                <x-primary-button wire:click="submitProof">إرسال للمراجعة</x-primary-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600">لا توجد اشتراكات بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
