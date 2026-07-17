<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

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
                    <p class="text-gray-600">لا توجد خطط متاحة حاليًا. تواصل مع مدرسك.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h4 class="font-medium mb-3">اشتراكاتك</h4>
            <div class="space-y-3">
                @forelse ($subscriptions as $subscription)
                    <div class="border rounded-lg p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-medium">{{ $subscription->plan->name }}</div>
                                <div class="text-sm text-gray-600">{{ $subscription->subject->name }} — {{ $subscription->teacher->name }}</div>
                                <div class="text-sm">
                                    الحالة:
                                    <span class="font-medium">{{ $subscription->status->label() }}</span>
                                    @if ($subscription->ends_at)
                                        — ينتهي {{ $subscription->ends_at->format('Y-m-d') }}
                                    @endif
                                </div>
                            </div>
                            @if ($subscription->status === \App\Enums\SubscriptionStatus::PendingPayment)
                                <x-secondary-button wire:click="startPayment({{ $subscription->id }})">إرسال إثبات فودافون</x-secondary-button>
                            @endif
                        </div>

                        @if ($payingSubscriptionId === $subscription->id)
                            <div class="mt-4 border-t pt-4 space-y-3">
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
