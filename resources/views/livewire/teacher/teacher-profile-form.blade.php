<div>
    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($publicUrl)
        <p class="mb-4 text-sm text-gray-600">
            صفحتك العامة:
            <a href="{{ $publicUrl }}" class="text-teal-700 hover:underline" target="_blank" rel="noopener">{{ $publicUrl }}</a>
        </p>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-input-label for="name" value="الاسم" />
            <x-text-input wire:model="name" id="name" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="phone" value="الهاتف" />
            <x-text-input wire:model="phone" id="phone" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="headline" value="تخصص / عنوان قصير" />
            <x-text-input wire:model="headline" id="headline" class="mt-1 block w-full" placeholder="مدرس رياضيات — ثانوية عامة" />
            <x-input-error :messages="$errors->get('headline')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="bio" value="نبذة عنك" />
            <textarea wire:model="bio" id="bio" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <x-input-error :messages="$errors->get('bio')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="vodafoneCashNumber" value="رقم فودافون كاش" />
            <x-text-input wire:model="vodafoneCashNumber" id="vodafoneCashNumber" class="mt-1 block w-full" />
            <p class="mt-1 text-xs text-gray-500">يظهر للطلاب وأولياء الأمور عند الدفع (غالبًا نهاية الشهر).</p>
            <x-input-error :messages="$errors->get('vodafoneCashNumber')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="paymentInstructions" value="تعليمات الدفع" />
            <textarea wire:model="paymentInstructions" id="paymentInstructions" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="حوّل باسم المدرس واكتب اسم الطالب في الملاحظات"></textarea>
            <x-input-error :messages="$errors->get('paymentInstructions')" class="mt-1" />
        </div>

        <div class="space-y-3 rounded-lg border border-teal-200 bg-teal-50/40 p-4">
            <h4 class="font-medium text-teal-950">مادتك (مادة واحدة فقط)</h4>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="catalog" class="text-teal-700">
                    اختيار من كتالوج السنتر
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="custom" class="text-teal-700">
                    كتابة اسم مادتي
                </label>
            </div>

            @if ($subjectMode === 'catalog')
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model="subjectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                <div>
                    <x-input-label value="الصف" />
                    <select wire:model="gradeId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                    <x-text-input wire:model="subjectName" class="mt-1 block w-full" placeholder="مثال: رياضيات" />
                    <x-input-error :messages="$errors->get('subjectName')" class="mt-1" />
                    <x-input-error :messages="$errors->get('subject_name')" class="mt-1" />
                </div>
            @endif
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" wire:model="isPubliclyVisible" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
                أظهر صفحتي في كتالوج المدرسين للعامة
                <span class="block text-xs text-gray-500">يلزم: نبذة أو عنوان + رقم فودافون + مادتك</span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('isPubliclyVisible')" class="mt-1" />

        <x-primary-button type="submit">حفظ البروفايل</x-primary-button>
    </form>
</div>
