<div>
    @if (session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($publicUrl)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold text-brand-800">صفحتك العامة</div>
                <a href="{{ $publicUrl }}" class="mt-0.5 block truncate text-sm text-brand-700 hover:underline">{{ $publicUrl }}</a>
            </div>
            <a href="{{ $publicUrl }}" class="btn-brand !px-3 !py-2 text-xs">فتح</a>
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="الاسم" />
                <x-text-input wire:model="name" id="name" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="phone" value="الهاتف" />
                <x-text-input wire:model="phone" id="phone" class="mt-1.5 block w-full" dir="ltr" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="headline" value="تخصص / عنوان قصير" />
            <x-text-input wire:model="headline" id="headline" class="mt-1.5 block w-full" placeholder="مدرس برمجة — أولى وتانية ثانوي" />
            <x-input-error :messages="$errors->get('headline')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="bio" value="نبذة عنك" />
            <textarea wire:model="bio" id="bio" rows="4" class="mt-1.5 block w-full"></textarea>
            <x-input-error :messages="$errors->get('bio')" class="mt-1" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="vodafoneCashNumber" value="رقم فودافون كاش" />
                <x-text-input wire:model="vodafoneCashNumber" id="vodafoneCashNumber" class="mt-1.5 block w-full" dir="ltr" />
                <p class="mt-1 text-xs text-ink-muted">يظهر للطلاب وأولياء الأمور عند الدفع.</p>
                <x-input-error :messages="$errors->get('vodafoneCashNumber')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="paymentInstructions" value="تعليمات الدفع" />
                <textarea wire:model="paymentInstructions" id="paymentInstructions" rows="3" class="mt-1.5 block w-full" placeholder="حوّل باسم المدرس واكتب اسم الطالب في الملاحظات"></textarea>
                <x-input-error :messages="$errors->get('paymentInstructions')" class="mt-1" />
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-brand-100 bg-brand-50/40 p-4 sm:p-5">
            <div>
                <h4 class="text-sm font-bold text-brand-950">مادتك (مادة واحدة فقط)</h4>
                <p class="mt-0.5 text-xs text-ink-muted">اختر من كتالوج السنتر أو أضف اسم مادتك.</p>
            </div>

            <div class="flex flex-wrap gap-4 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="catalog" class="text-brand-700">
                    اختيار من كتالوج السنتر
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="custom" class="text-brand-700">
                    كتابة اسم مادتي
                </label>
            </div>

            @if ($subjectMode === 'catalog')
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model="subjectId" class="mt-1.5 block w-full">
                        <option value="">اختر مادة</option>
                        @foreach ($catalogSubjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('subjectId')" class="mt-1" />
                    <x-input-error :messages="$errors->get('subject_id')" class="mt-1" />
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="الصف" />
                        <select wire:model="gradeId" class="mt-1.5 block w-full">
                            <option value="">اختر الصف</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                        <x-input-error :messages="$errors->get('grade_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="اسم المادة" />
                        <x-text-input wire:model="subjectName" class="mt-1.5 block w-full" placeholder="مثال: برمجة" />
                        <x-input-error :messages="$errors->get('subjectName')" class="mt-1" />
                        <x-input-error :messages="$errors->get('subject_name')" class="mt-1" />
                    </div>
                </div>
            @endif
        </div>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm">
            <input type="checkbox" wire:model="isPubliclyVisible" class="mt-0.5 rounded border-gray-300 text-brand-700 focus:ring-brand-500">
            <span>
                <span class="font-semibold text-ink">أظهر صفحتي في كتالوج المدرسين</span>
                <span class="mt-0.5 block text-xs text-ink-muted">يلزم: نبذة أو عنوان + رقم فودافون + مادتك</span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('isPubliclyVisible')" class="mt-1" />

        <div class="border-t border-slate-100 pt-4">
            <x-primary-button type="submit">حفظ البروفايل العام</x-primary-button>
        </div>
    </form>
</div>
