<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">مدفوعات {{ $student->name }}</h3>
        <p class="text-sm text-gray-500">الكود: {{ $student->student_code }} — يمكنك الاشتراك وإرسال إثبات فودافون كاش نيابة عنه.</p>
    </div>

    <div class="space-y-6">
        <div>
            <h4 class="font-medium mb-3">خطط الاشتراك</h4>
            <div class="space-y-3">
                @forelse ($plans as $plan)
                    <div class="border rounded-lg p-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-sm text-gray-600">{{ $plan->subject->name }} — {{ $plan->teacher?->name }}</div>
                            <div class="text-sm text-gray-500">{{ number_format($plan->price, 2) }} ج.م / {{ $plan->duration_days }} يوم</div>
                        </div>
                        <x-primary-button wire:click="enroll({{ $plan->id }})">اشترك للابن</x-primary-button>
                    </div>
                @empty
                    <p class="text-gray-600 text-sm">لا توجد خطط. تأكد أن الابن منضم لمدرّس لديه خطط نشطة.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h4 class="font-medium mb-3">الاشتراكات والدفع</h4>
            <div class="space-y-3">
                @forelse ($subscriptions as $subscription)
                    @php $latestPayment = $subscription->payments->first(); @endphp
                    <div class="border rounded-lg p-4 space-y-3">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-medium">{{ $subscription->plan->name }}</div>
                                <div class="text-sm text-gray-600">{{ $subscription->subject->name }} — {{ $subscription->teacher->name }}</div>
                                <div class="text-sm">الحالة: <span class="font-medium">{{ $subscription->status->label() }}</span></div>
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
                                <x-secondary-button wire:click="startPayment({{ $subscription->id }})">
                                    إرسال إثبات فودافون
                                </x-secondary-button>
                            @elseif ($latestPayment?->isPending())
                                <span class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">بانتظار مراجعة المدرس</span>
                            @endif
                        </div>

                        @if ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment && ! empty($instructions[$subscription->id]))
                            <div class="bg-slate-50 border rounded-md p-3 text-sm text-gray-700 space-y-1">
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
                            <div class="border-t pt-4 space-y-3">
                                <div>
                                    <x-input-label value="رقم عملية فودافون كاش" />
                                    <x-text-input wire:model="externalReference" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('externalReference')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="صورة وصل فودافون كاش (مطلوبة)" />
                                    <input type="file" wire:model="proof" class="block mt-1 w-full text-sm" accept="image/*" />
                                    <x-input-error :messages="$errors->get('proof')" class="mt-1" />
                                </div>
                                <x-primary-button wire:click="submitProof">إرسال للمراجعة</x-primary-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600 text-sm">لا توجد اشتراكات بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
