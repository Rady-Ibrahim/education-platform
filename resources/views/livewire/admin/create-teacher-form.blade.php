<div class="space-y-5">

    @if (session('teacher_created'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('teacher_created') }}
        </div>
    @endif

    {{-- ── نتيجة الإنشاء (كلمة المرور المؤقتة) ── --}}
    @if ($generatedPassword)
        <div class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50">
            <div class="flex items-center justify-between border-b border-amber-200 px-5 py-3">
                <div class="flex items-center gap-2 text-sm font-bold text-amber-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    بيانات تسجيل دخول المدرس — سلّمها له مرة واحدة
                </div>
                <button type="button" wire:click="clearResult"
                    class="rounded-lg p-1 text-amber-600 hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-2 px-5 py-4 text-sm">
                <div class="flex items-center gap-3">
                    <span class="w-28 shrink-0 font-semibold text-amber-900">الاسم</span>
                    <span class="text-amber-800">{{ $createdTeacherName }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-28 shrink-0 font-semibold text-amber-900">البريد</span>
                    <span class="font-mono text-amber-800" dir="ltr">{{ $createdTeacherEmail }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-28 shrink-0 font-semibold text-amber-900">كلمة المرور</span>
                    <span class="rounded-lg bg-amber-100 px-3 py-1 font-mono text-base font-bold tracking-wider text-amber-950 select-all">
                        {{ $generatedPassword }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-amber-700">
                    ⚠️ هذه الكلمة لن تظهر مرة أخرى — انسخها وسلّمها للمدرس الآن.
                    الحساب نشط وجاهز للدخول مباشرة.
                </p>
            </div>
        </div>
    @endif

    {{-- ── Form ── --}}
    <x-page-section title="إنشاء حساب مدرس" subtitle="الحساب يُفعَّل فوراً بدون انتظار — سلّم البيانات للمدرس.">
        <form wire:submit="save" class="space-y-5">

            {{-- Basic info --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="اسم المدرس *" />
                    <x-text-input wire:model="name" class="mt-1.5 block w-full" placeholder="مثلاً: أحمد محمود" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="البريد الإلكتروني *" />
                    <x-text-input wire:model="email" type="email" class="mt-1.5 block w-full" dir="ltr" placeholder="teacher@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="رقم الهاتف" />
                    <x-text-input wire:model="phone" class="mt-1.5 block w-full" dir="ltr" placeholder="01x xxxx xxxx" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="التخصص / العنوان" />
                    <x-text-input wire:model="headline" class="mt-1.5 block w-full" placeholder="مدرس رياضيات — ثانوي" />
                    <x-input-error :messages="$errors->get('headline')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="رقم فودافون كاش" />
                    <x-text-input wire:model="vodafoneCashNumber" class="mt-1.5 block w-full" dir="ltr" placeholder="01x xxxx xxxx" />
                    <x-input-error :messages="$errors->get('vodafoneCashNumber')" class="mt-1" />
                </div>
            </div>

            {{-- Subject --}}
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.1em] text-ink-muted">المادة الدراسية</p>

                <div class="mb-4 flex flex-wrap gap-4 text-sm">
                    @foreach (['catalog' => 'من كتالوج السنتر', 'custom' => 'مادة مخصصة', 'none' => 'بدون مادة الآن'] as $val => $label)
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="subjectMode" value="{{ $val }}"
                                class="text-brand-700 focus:ring-brand-500">
                            <span @class(['font-semibold text-ink' => $subjectMode === $val, 'text-ink-muted' => $subjectMode !== $val])>
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($subjectMode === 'catalog')
                    <div>
                        <x-input-label value="اختر المادة" />
                        <select wire:model="subjectId" class="mt-1.5 block w-full max-w-sm">
                            <option value="">— اختر مادة</option>
                            @foreach ($catalogSubjects as $subject)
                                <option value="{{ $subject->id }}">
                                    {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('subjectId')" class="mt-1" />
                    </div>

                @elseif ($subjectMode === 'custom')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label value="الصف الدراسي" />
                            <select wire:model="gradeId" class="mt-1.5 block w-full">
                                <option value="">— اختر الصف</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="اسم المادة" />
                            <x-text-input wire:model="subjectName" class="mt-1.5 block w-full" placeholder="مثلاً: فيزياء" />
                            <x-input-error :messages="$errors->get('subjectName')" class="mt-1" />
                        </div>
                    </div>

                @else
                    <p class="text-xs text-ink-muted">يمكن للمدرس إضافة مادته لاحقاً من البروفايل.</p>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <p class="text-xs text-ink-muted">
                    كلمة مرور عشوائية تُولَّد تلقائياً — ستظهر مرة واحدة بعد الحفظ.
                </p>
                <x-primary-button wire:loading.attr="disabled">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span wire:loading.remove>إنشاء الحساب</span>
                    <span wire:loading>جاري الإنشاء…</span>
                </x-primary-button>
            </div>
        </form>
    </x-page-section>
</div>
