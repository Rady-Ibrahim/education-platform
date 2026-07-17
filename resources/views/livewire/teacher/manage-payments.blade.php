<div>
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">إجمالي المحصّل المؤكد</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ number_format($confirmedTotal, 0) }} <span class="text-base text-ink-muted">ج.م</span></div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">مدفوعات بانتظار المراجعة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $pendingPayments->total() }}</div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">اشتراكات بانتظار الدفع</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $pendingSubscriptions->count() }}</div>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
            <h3 class="text-base font-bold text-ink">بيانات فودافون كاش</h3>
            <p class="mt-1 text-sm text-ink-muted">تظهر لأولياء الأمور عند الدفع.</p>
            <div class="mt-4">
                <livewire:teacher.payment-settings-form />
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 p-5">
                <h3 class="text-base font-bold text-ink">إنشاء خطة اشتراك</h3>
                <div class="mt-4 grid gap-3">
                    <div>
                        <x-input-label value="اسم الخطة" />
                        <x-text-input wire:model="newPlanName" class="mt-1 block w-full" placeholder="اشتراك شهري" />
                    </div>
                    <div>
                        <x-input-label value="المادة" />
                        <select wire:model="newPlanSubjectId" class="mt-1 block w-full">
                            <option value="">اختر المادة</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="السعر (ج.م)" />
                        <x-text-input wire:model="newPlanPrice" type="number" step="0.01" class="mt-1 block w-full" />
                    </div>
                    <x-primary-button type="button" wire:click="createPlan">حفظ الخطة</x-primary-button>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 p-5">
                <h3 class="text-base font-bold text-ink">تسجيل طالب على خطة</h3>
                <div class="mt-4 grid gap-3">
                    <select wire:model="enrollStudentId" class="block w-full">
                        <option value="">اختر الطالب</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="enrollPlanId" class="block w-full">
                        <option value="">اختر الخطة</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->subject->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button type="button" wire:click="enrollStudent">تسجيل على الخطة</x-primary-button>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 p-5">
            <h3 class="text-base font-bold text-ink">تسجيل دفعة كاش</h3>
            <p class="mt-1 text-sm text-ink-muted">يسجّل الدفع ويفعّل الاشتراك مباشرة.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <select wire:model.live="cashStudentId" class="block w-full">
                    <option value="">اختر الطالب</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
                <select wire:model="cashSubscriptionId" class="block w-full">
                    <option value="">اشتراك بانتظار الدفع</option>
                    @foreach ($pendingSubscriptions as $subscription)
                        <option value="{{ $subscription->id }}">
                            {{ $subscription->student->name }} — {{ $subscription->plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mt-3">
                <x-text-input wire:model="cashNotes" placeholder="ملاحظات (اختياري)" class="block w-full" />
            </div>
            <div class="mt-3">
                <x-primary-button type="button" wire:click="recordCash">تسجيل وتفعيل</x-primary-button>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-bold text-ink">مدفوعات بانتظار المراجعة</h3>
                <x-status-badge tone="warning">{{ $pendingPayments->total() }} معلّق</x-status-badge>
            </div>

            <div class="space-y-3">
                @forelse ($pendingPayments as $payment)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-semibold text-ink">{{ $payment->student->name }}</div>
                                <div class="mt-1 text-sm text-ink-muted">
                                    {{ $payment->channel->label() }} — {{ number_format($payment->amount, 2) }} ج.م
                                </div>
                                @if ($payment->external_reference)
                                    <div class="mt-1 text-sm text-ink-muted">رقم العملية: {{ $payment->external_reference }}</div>
                                @endif
                                @if ($payment->proof_path)
                                    <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" class="mt-1 inline-flex text-sm font-semibold text-brand-700">عرض الإثبات</a>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-primary-button type="button" wire:click="confirm({{ $payment->id }})">تأكيد</x-primary-button>
                                <x-danger-button type="button" wire:click="startReject({{ $payment->id }})">رفض</x-danger-button>
                            </div>
                        </div>

                        @if ($rejectingPaymentId === $payment->id)
                            <div class="mt-3 space-y-2 rounded-xl border border-rose-200 bg-rose-50 p-3">
                                <x-text-input wire:model="rejectionReason" placeholder="سبب الرفض" class="block w-full" />
                                <x-danger-button type="button" wire:click="confirmReject">تأكيد الرفض</x-danger-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-ink-muted">لا توجد مدفوعات معلّقة.</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $pendingPayments->links() }}</div>
        </section>
    </div>
</div>
