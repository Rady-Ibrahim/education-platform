<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($generatedPassword)
        <div class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md p-3 space-y-1">
            <div>
                كلمة مرور الطالب المؤقتة:
                <span class="font-mono font-semibold">{{ $generatedPassword }}</span>
            </div>
            @if ($generatedStudentCode)
                <div>
                    كود الطالب لولي الأمر:
                    <span class="font-mono font-semibold">{{ $generatedStudentCode }}</span>
                </div>
            @endif
            <div class="text-xs">سلّم البيانات للطالب/ولي الأمر مرة واحدة.</div>
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-input-label value="اسم الطالب" />
            <x-text-input wire:model="name" class="block mt-1 w-full" type="text" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="البريد الإلكتروني" />
            <x-text-input wire:model="email" class="block mt-1 w-full" type="email" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="الهاتف (اختياري)" />
            <x-text-input wire:model="phone" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <x-primary-button>إضافة الطالب</x-primary-button>
    </form>
</div>
