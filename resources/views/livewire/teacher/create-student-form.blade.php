<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($generatedPassword)
        <div class="mb-4 space-y-1 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
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
            <x-text-input wire:model="name" class="mt-1 block w-full" type="text" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="البريد الإلكتروني" />
            <x-text-input wire:model="email" class="mt-1 block w-full" type="email" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="الصف الدراسي" />
            <select wire:model.live="gradeId" class="mt-1 block w-full">
                <option value="">اختر الصف — أولى ثانوي / تانية…</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gradeId')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="المجموعة (اختياري)" />
            <select wire:model="groupId" class="mt-1 block w-full" @disabled(! $gradeId)>
                <option value="">بدون مجموعة الآن</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">
                        {{ $group->name }}
                        @if ($group->schedule_note) — {{ $group->schedule_note }} @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('groupId')" class="mt-2" />
            @if ($gradeId && $groups->isEmpty())
                <p class="mt-1 text-xs text-ink-muted">
                    لا توجد مجموعات لهذا الصف —
                    <a href="{{ route('teacher.groups') }}" class="link-brand" wire:navigate>أنشئ مجموعة</a>
                </p>
            @endif
        </div>
        <div>
            <x-input-label value="الهاتف (اختياري)" />
            <x-text-input wire:model="phone" class="mt-1 block w-full" type="text" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <x-primary-button>إضافة الطالب</x-primary-button>
    </form>
</div>
