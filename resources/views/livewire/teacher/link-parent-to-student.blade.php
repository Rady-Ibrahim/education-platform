<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="ربط ولي أمر بطالب" subtitle="اربط ولي أمر بإيميله بحساب طالب في مكتبك.">
        <form wire:submit="submit" class="space-y-4">
            <div>
                <x-input-label value="الطالب" />
                <select wire:model="studentId" class="mt-1.5 block w-full">
                    <option value="">اختر الطالب</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('studentId')" />
            </div>
            <div>
                <x-input-label value="إيميل ولي الأمر" />
                <x-text-input wire:model="parentEmail" type="email" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('parentEmail')" />
            </div>
            <div>
                <x-input-label value="صلة القرابة" />
                <select wire:model="relationship" class="mt-1.5 block w-full">
                    <option value="">اختياري</option>
                    <option value="father">أب</option>
                    <option value="mother">أم</option>
                    <option value="guardian">وصي</option>
                </select>
            </div>
            <x-primary-button type="submit">ربط ولي الأمر</x-primary-button>
        </form>
    </x-page-section>
</div>
