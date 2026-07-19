<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">محصّل مؤكد</div>
            <div class="kpi-value">{{ number_format($confirmedTotal, 0) }} <span class="text-sm font-semibold text-ink-muted">ج.م</span></div>
        </div>
        <div class="kpi-card-warn">
            <div class="text-[11px] font-semibold uppercase tracking-[0.06em] text-amber-800">عليه فلوس الشهر</div>
            <div class="mt-1.5 text-2xl font-bold tracking-tight text-amber-950">{{ number_format($owingTotal, 0) }} <span class="text-sm font-semibold">ج.م</span></div>
            <div class="mt-1 text-xs text-amber-800">{{ $owingCount }} طالب</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">مصاريف مفتوحة</div>
            <div class="kpi-value">{{ number_format($openFeesTotal, 0) }} <span class="text-sm font-semibold text-ink-muted">ج.م</span></div>
            <div class="mt-1 text-xs text-ink-muted">{{ $openFeesCount }} بند</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">فودافون بانتظارك</div>
            <div class="kpi-value">{{ $pendingVodafoneCount }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <x-page-tabs
        :active="$tab"
        :tabs="[
            'cash' => ['label' => 'دفتر الشهر', 'badge' => $owingCount],
            'fees' => ['label' => 'مصاريف وكتب', 'badge' => $openFeesCount],
            'vodafone' => ['label' => 'مراجعة فودافون', 'badge' => $pendingVodafoneCount],
            'plans' => ['label' => 'الخطط والتسجيل'],
            'settings' => ['label' => 'إعدادات الدفع'],
        ]"
    />

    {{-- Panels --}}
    @if ($tab === 'cash')
        <x-page-section
            title="دفتر التحصيل الشهري"
            subtitle="اختر الشهر، ولّد المستحقات، ثم سجّل تحصيل كامل أو جزئي مع خصم وإيصال."
        >
            <x-slot:actions>
                <x-secondary-button type="button" wire:click="generateMonth">توليد مستحقات الشهر</x-secondary-button>
            </x-slot:actions>

            <div class="space-y-4">
                <div class="grid gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <div>
                        <x-input-label value="الشهر" />
                        <x-text-input wire:model.live="billingMonth" type="month" class="mt-1.5 block w-full" />
                    </div>
                    <div class="lg:col-span-2">
                        <x-input-label value="بحث" />
                        <x-text-input wire:model.live.debounce.300ms="cashSearch" class="mt-1.5 block w-full" placeholder="اسم / كود / موبايل" />
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                        <input type="checkbox" wire:model.live="owingOnly" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        عليه فلوس فقط
                    </label>
                </div>

                @if ($collectCharge)
                    <div class="rounded-2xl border border-accent/40 bg-[#FFF8E8] p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-ink">تحصيل — {{ $collectCharge->student?->name }}</div>
                                <div class="mt-0.5 text-xs text-ink-muted">
                                    متبقي {{ number_format($collectCharge->remainingAmount(), 0) }} ج.م من أصل {{ number_format((float) $collectCharge->expected_amount, 0) }}
                                </div>
                            </div>
                            <button type="button" class="text-sm text-ink-muted hover:text-ink" wire:click="cancelCollect">إلغاء</button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div>
                                <x-input-label value="المبلغ المستلم" />
                                <x-text-input wire:model="collectAmount" type="number" step="0.5" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('collectAmount')" />
                            </div>
                            <div>
                                <x-input-label value="خصم على الشهر" />
                                <x-text-input wire:model="collectDiscount" type="number" step="0.5" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('collectDiscount')" />
                            </div>
                            <div>
                                <x-input-label value="ملاحظة" />
                                <x-text-input wire:model="cashNotes" class="mt-1.5 block w-full" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <x-primary-button type="button" wire:click="collectCharge">حفظ التحصيل + إيصال</x-primary-button>
                        </div>
                    </div>
                @endif

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الشهر</th>
                                <th>المستحق</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                <tr>
                                    <td>
                                        <div class="font-semibold text-ink">{{ $charge->student?->name }}</div>
                                        <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $charge->student?->student_code }}</div>
                                        <div class="text-xs text-ink-muted">{{ $charge->subscription?->plan?->name }}</div>
                                    </td>
                                    <td class="text-sm">{{ $charge->monthLabel() }}</td>
                                    <td class="font-bold tabular-nums">{{ number_format((float) $charge->expected_amount, 0) }}</td>
                                    <td class="tabular-nums">{{ number_format($charge->paidAmount(), 0) }}</td>
                                    <td @class(['font-bold tabular-nums', 'text-amber-800' => $charge->remainingAmount() > 0])>
                                        {{ number_format($charge->remainingAmount(), 0) }}
                                    </td>
                                    <td>
                                        <span @class([
                                            'rounded-lg px-2 py-1 text-xs font-bold',
                                            'bg-amber-50 text-amber-900' => $charge->status === \App\Enums\ChargeStatus::Due,
                                            'bg-orange-50 text-orange-900' => $charge->status === \App\Enums\ChargeStatus::Partial,
                                            'bg-emerald-50 text-emerald-800' => $charge->status === \App\Enums\ChargeStatus::Paid,
                                            'bg-slate-100 text-slate-700' => $charge->status === \App\Enums\ChargeStatus::Waived,
                                        ])>{{ $charge->status->label() }}</span>
                                    </td>
                                    <td class="space-x-2 space-x-reverse text-end text-sm">
                                        @if ($charge->status->isOpen())
                                            <button type="button" class="btn-brand !px-3 !py-2 text-xs" wire:click="collectFull({{ $charge->id }})" wire:confirm="تحصيل المتبقي كاملًا؟">
                                                كامل
                                            </button>
                                            <button type="button" class="link-brand" wire:click="startCollect({{ $charge->id }})">جزئي</button>
                                        @else
                                            <span class="text-xs text-ink-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-sm text-ink-muted">
                                        لا توجد مستحقات لهذا الشهر.
                                        اضغط «توليد مستحقات الشهر» أو سجّل الطلاب من تاب الخطط.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-page-section>
    @endif

    @if ($tab === 'fees')
        <div class="grid gap-5 lg:grid-cols-5">
            <x-page-section
                class="lg:col-span-2"
                title="تسجيل مصروف"
                subtitle="كتب، مستلزمات، أو أي بند — ولي الأمر يدفع فودافون أو تحصّله كاش من الطالب."
            >
                <div class="space-y-3">
                    <div>
                        <x-input-label value="العنوان" />
                        <x-text-input wire:model="feeTitle" class="mt-1.5 block w-full" placeholder="مثال: كتاب برمجة أولى ثانوي" />
                        <x-input-error :messages="$errors->get('feeTitle')" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <x-input-label value="النوع" />
                            <select wire:model="feeCategory" class="mt-1.5 block w-full">
                                @foreach ($feeCategories as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="المبلغ (ج.م)" />
                            <x-text-input wire:model="feeAmount" type="number" step="0.5" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('feeAmount')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="الطالب" />
                        <select wire:model="feeStudentId" class="mt-1.5 block w-full">
                            <option value="">اختر الطالب</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('feeStudentId')" />
                    </div>
                    <div>
                        <x-input-label value="المادة (اختياري)" />
                        <select wire:model="feeSubjectId" class="mt-1.5 block w-full">
                            <option value="">—</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="ملاحظة" />
                        <x-text-input wire:model="feeNotes" class="mt-1.5 block w-full" />
                    </div>
                    <x-primary-button type="button" wire:click="createFee">حفظ المصروف</x-primary-button>
                </div>
            </x-page-section>

            <x-page-section
                class="lg:col-span-3"
                title="دفتر المصاريف"
                subtitle="نفس البند يُسدَّد إما كاش من الطالب أو فودافون من ولي الأمر — مرة واحدة فقط."
            >
                <div class="mb-4 grid gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3 sm:grid-cols-3 sm:items-end">
                    <div class="sm:col-span-2">
                        <x-input-label value="بحث" />
                        <x-text-input wire:model.live.debounce.300ms="feeSearch" class="mt-1.5 block w-full" placeholder="عنوان / طالب / كود" />
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                        <input type="checkbox" wire:model.live="feeOpenOnly" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        مفتوح فقط
                    </label>
                </div>

                @if ($collectFee)
                    <div class="mb-4 rounded-2xl border border-accent/40 bg-[#FFF8E8] p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-ink">تحصيل كاش — {{ $collectFee->student?->name }}</div>
                                <div class="mt-0.5 text-xs text-ink-muted">
                                    {{ $collectFee->title }} — متبقي {{ number_format($collectFee->remainingAmount(), 0) }} ج.م
                                </div>
                            </div>
                            <button type="button" class="text-sm text-ink-muted hover:text-ink" wire:click="cancelCollectFee">إلغاء</button>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div>
                                <x-input-label value="المبلغ المستلم" />
                                <x-text-input wire:model="collectFeeAmount" type="number" step="0.5" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('collectFeeAmount')" />
                            </div>
                            <div>
                                <x-input-label value="خصم" />
                                <x-text-input wire:model="collectFeeDiscount" type="number" step="0.5" class="mt-1.5 block w-full" />
                            </div>
                            <div>
                                <x-input-label value="ملاحظة" />
                                <x-text-input wire:model="collectFeeNotes" class="mt-1.5 block w-full" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <x-primary-button type="button" wire:click="collectFee">حفظ التحصيل + إيصال</x-primary-button>
                        </div>
                    </div>
                @endif

                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>البند</th>
                                <th>الطالب</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($studentFees as $fee)
                                <tr>
                                    <td>
                                        <div class="font-semibold text-ink">{{ $fee->title }}</div>
                                        <div class="text-xs text-ink-muted">{{ $fee->category->label() }} — {{ number_format((float) $fee->expected_amount, 0) }} ج.م</div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-ink">{{ $fee->student?->name }}</div>
                                        <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $fee->student?->student_code }}</div>
                                    </td>
                                    <td @class(['font-bold tabular-nums', 'text-amber-800' => $fee->remainingAmount() > 0])>
                                        {{ number_format($fee->remainingAmount(), 0) }}
                                    </td>
                                    <td>
                                        <span @class([
                                            'rounded-lg px-2 py-1 text-xs font-bold',
                                            'bg-amber-50 text-amber-900' => $fee->status === \App\Enums\ChargeStatus::Due,
                                            'bg-orange-50 text-orange-900' => $fee->status === \App\Enums\ChargeStatus::Partial,
                                            'bg-emerald-50 text-emerald-800' => $fee->status === \App\Enums\ChargeStatus::Paid,
                                            'bg-slate-100 text-slate-700' => $fee->status === \App\Enums\ChargeStatus::Waived,
                                        ])>{{ $fee->status->label() }}</span>
                                    </td>
                                    <td class="space-x-2 space-x-reverse text-end text-sm">
                                        @if ($fee->status->isOpen())
                                            <button type="button" class="btn-brand !px-3 !py-2 text-xs" wire:click="collectFeeFull({{ $fee->id }})" wire:confirm="تحصيل المتبقي كاملًا من الطالب؟">
                                                كاش كامل
                                            </button>
                                            <button type="button" class="link-brand" wire:click="startCollectFee({{ $fee->id }})">جزئي</button>
                                            <button type="button" class="text-xs text-ink-muted hover:text-rose-700" wire:click="waiveFee({{ $fee->id }})" wire:confirm="إعفاء هذا المصروف؟">إعفاء</button>
                                        @else
                                            <span class="text-xs text-ink-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-sm text-ink-muted">
                                        لا توجد مصاريف. سجّل بندًا جديدًا من النموذج على اليسار.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-page-section>
        </div>
    @endif

    @if ($tab === 'vodafone')
        <x-page-section
            title="مراجعة فودافون كاش"
            subtitle="إثباتات أولياء الأمور — راجع الصورة ورقم العملية ثم أكّد أو ارفض."
        >
            <div class="space-y-3">
                @forelse ($pendingVodafone as $payment)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-semibold text-ink">{{ $payment->student->name }}</div>
                                <div class="mt-1 text-sm text-ink-muted">
                                    {{ number_format($payment->amount, 2) }} ج.م
                                    @if ($payment->studentFee)
                                        — مصروف: {{ $payment->studentFee->title }}
                                    @elseif ($payment->subscription?->plan)
                                        — {{ $payment->subscription->plan->name }}
                                    @endif
                                </div>
                                @if ($payment->external_reference)
                                    <div class="mt-1 text-sm text-ink-muted">رقم العملية: <span class="font-mono">{{ $payment->external_reference }}</span></div>
                                @endif
                                @if ($payment->proof_path)
                                    <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-sm font-semibold text-brand-700">عرض صورة الوصل ↗</a>
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
                    <div class="empty-state">
                        <p class="text-sm text-ink-muted">لا توجد إثباتات فودافون معلّقة.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $pendingVodafone->links() }}</div>
        </x-page-section>
    @endif

    @if ($tab === 'plans')
        <div class="grid gap-5 lg:grid-cols-2">
            <x-page-section title="خطة اشتراك شهرية" subtitle="عادةً اشتراك 30 يوم — التحصيل نهاية الشهر.">
                <div class="space-y-3">
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
            </x-page-section>

            <x-page-section title="تسجيل طالب على خطة" subtitle="بعد التسجيل يظهر في دفتر الشهر الحالي.">
                <div class="space-y-3">
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
            </x-page-section>
        </div>
    @endif

    @if ($tab === 'settings')
        <x-page-section
            title="إعدادات فودافون كاش"
            subtitle="الرقم والتعليمات التي تظهر لولي الأمر عند التحويل."
        >
            <div class="max-w-xl">
                <livewire:teacher.payment-settings-form />
            </div>
        </x-page-section>
    @endif
</div>
