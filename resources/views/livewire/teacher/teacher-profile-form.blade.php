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
        <div>
            <x-input-label value="المواد التي تدرّسها" />
            <div class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3">
                @forelse ($subjects as $subject)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="subjectIds" value="{{ $subject->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">لا توجد مواد بعد. اطلب من الإدارة إنشاء الهيكل الأكاديمي.</p>
                @endforelse
            </div>
            <x-input-error :messages="$errors->get('subjectIds')" class="mt-1" />
            <x-input-error :messages="$errors->get('subjectIds.*')" class="mt-1" />
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" wire:model="isPubliclyVisible" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
                أظهر صفحتي في كتالوج المدرسين للعامة
                <span class="block text-xs text-gray-500">يلزم: نبذة أو عنوان + رقم فودافون + مادة واحدة على الأقل</span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('isPubliclyVisible')" class="mt-1" />

        <x-primary-button type="submit">حفظ البروفايل</x-primary-button>
    </form>
</div>
