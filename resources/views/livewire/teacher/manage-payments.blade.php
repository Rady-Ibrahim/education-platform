<div>
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <div class="text-xs font-semibold text-ink-muted">محصّل مؤكد</div>
            <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($confirmedTotal, 0) }} <span class="text-sm text-ink-muted">ج.م</span></div>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
            <div class="text-xs font-semibold text-amber-800">كاش مستحق (دفتر التحصيل)</div>
            <div class="mt-1 text-2xl font-bold text-amber-950">{{ number_format($cashDueTotal, 0) }} <span class="text-sm">ج.م</span></div>
            <div class="mt-0.5 text-xs text-amber-800">{{ $pendingCash->count() }} طالب</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <div class="text-xs font-semibold text-ink-muted">فودافون بانتظارك</div>
            <div class="mt-1 text-2xl font-bold text-ink">{{ $pendingVodafoneCount }}</div>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @foreach ([
            'cash' => 'تحصيل الكاش',
            'vodafone' => 'مراجعة فودافون',
            'plans' => 'الخطط والتسجيل',
            'settings' => 'إعدادات الدفع',
        ] as $key => $label)
            <button
                type="button"
                wire:click="setTab('{{ $key }}')"
                @class([
                    'rounded-xl px-4 py-2 text-sm font-bold transition',
                    'bg-brand-700 text-white shadow-soft' => $tab === $key,
                    'bg-white text-ink-muted border border-slate-200 hover:bg-slate-50' => $tab !== $key,
                ])
            >
                {{ $label }}
                @if ($key === 'cash' && $pendingCash->isNotEmpty())
                    <span @class(['ms-1 rounded-md px-1.5 py-0.5 text-[10px]', 'bg-white/20' => $tab === $key, 'bg-amber-100 text-amber-800' => $tab !== $key])>{{ $pendingCash->count() }}</span>
                @endif
                @if ($key === 'vodafone' && $pendingVodafoneCount > 0)
                    <span @class(['ms-1 rounded-md px-1.5 py-0.5 text-[10px]', 'bg-white/20' => $tab === $key, 'bg-rose-100 text-rose-800' => $tab !== $key])>{{ $pendingVodafoneCount }}</span>
                @endif
            </button>
        @endforeach
    </div>

    @if ($tab === 'cash')
        <section class="space-y-4">
            <div class="rounded-2xl border border-brand-100 bg-brand-50/40 px-4 py-3 text-sm text-brand-950">
                <span class="font-bold">دفتر تحصيل نهاية الشهر:</span>
                الطلاب المسجّلين على خطة ولسه ما دفعوش — اضغط «استلمت الكاش» لما الطالب يدفع في السنتر.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label value="بحث في دفتر التحصيل" />
                    <x-text-input wire:model.live.debounce.300ms="cashSearch" class="mt-1.5 block w-full" placeholder="اسم الطالب أو الكود أو الموبايل" />
                </div>
                <div class="sm:w-64">
                    <x-input-label value="ملاحظة عامة (اختياري)" />
                    <x-text-input wire:model="cashNotes" class="mt-1.5 block w-full" placeholder="مثال: تحصيل مارس" />
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>الخطة</th>
                            <th>المبلغ</th>
                            <th>من متى منتظر</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingCash as $subscription)
                            <tr>
                                <td>
                                    <div class="font-semibold text-ink">{{ $subscription->student?->name }}</div>
                                    <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $subscription->student?->student_code }}</div>
                                </td>
                                <td>
                                    <div>{{ $subscription->plan?->name }}</div>
                                    <div class="text-xs text-ink-muted">{{ $subscription->subject?->name }}</div>
                                </td>
                                <td class="font-bold text-ink">{{ number_format((float) $subscription->plan?->price, 0) }} ج.م</td>
                                <td class="text-sm text-ink-muted">{{ $subscription->created_at?->diffForHumans() }}</td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        wire:click="collectCash({{ $subscription->id }})"
                                        wire:confirm="تأكيد استلام كاش {{ $subscription->student?->name }} بمبلغ {{ number_format((float) $subscription->plan?->price, 0) }} ج.م؟"
                                        class="btn-brand !px-3 !py-2 text-xs"
                                    >
                                        استلمت الكاش
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-sm text-ink-muted">
                                    مفيش مستحقات كاش دلوقتي.
                                    @if ($plans->isNotEmpty())
                                        سجّل الطلاب على خطة من تاب «الخطط والتسجيل».
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'vodafone')
        <section class="space-y-4">
            <p class="text-sm text-ink-muted">إثباتات فودافون كاش من أولياء الأمور — راجع الصورة ورقم العملية ثم أكّد أو ارفض.</p>

            <div class="space-y-3">
                @forelse ($pendingVodafone as $payment)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-semibold text-ink">{{ $payment->student->name }}</div>
                                <div class="mt-1 text-sm text-ink-muted">
                                    {{ number_format($payment->amount, 2) }} ج.م
                                    @if ($payment->subscription?->plan)
                                        — {{ $payment->subscription->plan->name }}
                                    @endif
                                </div>
                                @if ($payment->external_reference)
                                    <div class="mt-1 text-sm text-ink-muted">رقم العملية: <span class="font-mono">{{ $payment->external_reference }}</span></div>
                                @endif
                                @if ($payment->proof_path)
                                    <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" class="mt-1 inline-flex text-sm font-semibold text-brand-700">عرض صورة الوصل</a>
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
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-ink-muted">لا توجد إثباتات فودافون معلّقة.</p>
                @endforelse
            </div>

            <div>{{ $pendingVodafone->links() }}</div>
        </section>
    @endif

    @if ($tab === 'plans')
        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                <h3 class="text-sm font-bold text-ink">خطة اشتراك شهرية</h3>
                <p class="mt-1 text-xs text-ink-muted">معظم المدرسين: اشتراك 30 يوم — التحصيل نهاية الشهر.</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <x-input-label value="اسم الخطة" />
                        <x-text-input wire:model="newPlanName" class="mt-1.5 block w-full" />
                    </div>
                    <div>
                        <x-input-label value="المادة" />
                        <select wire:model="newPlanSubjectId" class="mt-1.5 block w-full">
                            <option value="">اختر المادة</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->grade?->name }} / {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <x-input-label value="السعر (ج.م)" />
                            <x-text-input wire:model="newPlanPrice" type="number" step="0.01" class="mt-1.5 block w-full" />
                        </div>
                        <div>
                            <x-input-label value="المدة (يوم)" />
                            <x-text-input wire:model="newPlanDays" type="number" class="mt-1.5 block w-full" />
                        </div>
                    </div>
                    <x-primary-button type="button" wire:click="createPlan">حفظ الخطة</x-primary-button>
                </div>

                @if ($plans->isNotEmpty())
                    <ul class="mt-5 space-y-2 border-t border-slate-100 pt-4">
                        @foreach ($plans as $plan)
                            <li class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                                <span class="font-medium text-ink">{{ $plan->name }}</span>
                                <span class="text-ink-muted">{{ number_format($plan->price, 0) }} ج.م / {{ $plan->duration_days }} يوم</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                <h3 class="text-sm font-bold text-ink">تسجيل طالب على خطة</h3>
                <p class="mt-1 text-xs text-ink-muted">بعد التسجيل هيظهر في دفتر تحصيل الكاش لحد الدفع.</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <x-input-label value="الطالب" />
                        <select wire:model="enrollStudentId" class="mt-1.5 block w-full">
                            <option value="">اختر الطالب</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="الخطة" />
                        <select wire:model="enrollPlanId" class="mt-1.5 block w-full">
                            <option value="">اختر الخطة</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ number_format($plan->price, 0) }} ج.م</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button type="button" wire:click="enrollStudent">تسجيل في دفتر التحصيل</x-primary-button>
                </div>
            </div>
        </section>
    @endif

    @if ($tab === 'settings')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
            <h3 class="text-sm font-bold text-ink">فودافون كاش لأولياء الأمور</h3>
            <p class="mt-1 text-sm text-ink-muted">الرقم والتعليمات اللي تظهر لولي الأمر لما يحوّل في نهاية الشهر.</p>
            <div class="mt-4 max-w-xl">
                <livewire:teacher.payment-settings-form />
            </div>
        </section>
    @endif
</div>
