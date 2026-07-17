<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6">
        <div class="border rounded-lg p-4 bg-gray-50">
            <div class="text-sm text-gray-600">إجمالي المحصّل المؤكد</div>
            <div class="text-2xl font-semibold">{{ number_format($confirmedTotal, 2) }} ج.م</div>
        </div>

        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">إنشاء خطة اشتراك</h4>
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <x-input-label value="اسم الخطة" />
                    <x-text-input wire:model="newPlanName" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model="newPlanSubjectId" class="block mt-1 w-full border-gray-300 rounded-md">
                        <option value="">اختر المادة</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="السعر (ج.م)" />
                    <x-text-input wire:model="newPlanPrice" type="number" step="0.01" class="block mt-1 w-full" />
                </div>
            </div>
            <x-primary-button wire:click="createPlan">حفظ الخطة</x-primary-button>
        </div>

        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">تسجيل طالب على خطة</h4>
            <div class="grid gap-3 md:grid-cols-2">
                <select wire:model="enrollStudentId" class="border-gray-300 rounded-md">
                    <option value="">اختر الطالب</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
                <select wire:model="enrollPlanId" class="border-gray-300 rounded-md">
                    <option value="">اختر الخطة</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button wire:click="enrollStudent">تسجيل</x-primary-button>
        </div>

        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">تسجيل دفعة كاش</h4>
            <div class="grid gap-3 md:grid-cols-2">
                <select wire:model="cashStudentId" class="border-gray-300 rounded-md">
                    <option value="">اختر الطالب</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
                <select wire:model="cashSubscriptionId" class="border-gray-300 rounded-md">
                    <option value="">اشتراك بانتظار الدفع</option>
                    @foreach ($pendingSubscriptions as $subscription)
                        <option value="{{ $subscription->id }}">
                            {{ $subscription->student->name }} — {{ $subscription->plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-text-input wire:model="cashNotes" placeholder="ملاحظات (اختياري)" class="block w-full" />
            <x-primary-button wire:click="recordCash">تسجيل وتفعيل</x-primary-button>
        </div>

        <div>
            <h4 class="font-medium mb-3">مدفوعات بانتظار المراجعة</h4>
            <div class="space-y-4">
                @forelse ($pendingPayments as $payment)
                    <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-medium">{{ $payment->student->name }}</div>
                            <div class="text-sm text-gray-600">
                                {{ $payment->channel->label() }} — {{ number_format($payment->amount, 2) }} ج.م
                            </div>
                            @if ($payment->external_reference)
                                <div class="text-sm text-gray-500">رقم العملية: {{ $payment->external_reference }}</div>
                            @endif
                            @if ($payment->proof_path)
                                <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" class="text-sm text-indigo-600">عرض الإثبات</a>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                            <x-danger-button wire:click="startReject({{ $payment->id }})">رفض</x-danger-button>
                        </div>
                    </div>

                    @if ($rejectingPaymentId === $payment->id)
                        <div class="border rounded-lg p-4 bg-red-50 space-y-2">
                            <x-text-input wire:model="rejectionReason" placeholder="سبب الرفض" class="block w-full" />
                            <x-danger-button wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                        </div>
                    @endif
                @empty
                    <p class="text-gray-600">لا توجد مدفوعات معلّقة.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $pendingPayments->links() }}</div>
        </div>
    </div>
</div>
