<div class="space-y-5">
    @if (session('academic_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('academic_status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <x-page-section title="المراحل">
            <ul class="mb-3 max-h-64 space-y-1 overflow-y-auto">
                @foreach ($stages as $stage)
                    <li>
                        <button
                            type="button"
                            wire:click="selectStage({{ $stage->id }})"
                            @class([
                                'w-full rounded-xl px-3 py-2 text-start text-sm transition',
                                'bg-brand-50 font-semibold text-brand-900' => $selectedStageId === $stage->id,
                                'text-ink hover:bg-slate-50' => $selectedStageId !== $stage->id,
                            ])
                        >
                            {{ $stage->name }}
                            @unless ($stage->is_active)
                                <span class="text-xs text-rose-600">(موقوف)</span>
                            @endunless
                        </button>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="createStage" class="space-y-2 border-t border-slate-100 pt-3">
                <x-text-input wire:model="stageName" class="w-full" placeholder="اسم مرحلة جديدة" />
                <x-input-error :messages="$errors->get('stageName')" />
                <x-primary-button class="w-full justify-center">إضافة مرحلة</x-primary-button>
            </form>
        </x-page-section>

        <x-page-section title="الصفوف">
            <ul class="mb-3 max-h-64 space-y-1 overflow-y-auto">
                @forelse ($grades as $grade)
                    <li>
                        <button
                            type="button"
                            wire:click="selectGrade({{ $grade->id }})"
                            @class([
                                'w-full rounded-xl px-3 py-2 text-start text-sm transition',
                                'bg-brand-50 font-semibold text-brand-900' => $selectedGradeId === $grade->id,
                                'text-ink hover:bg-slate-50' => $selectedGradeId !== $grade->id,
                            ])
                        >
                            {{ $grade->name }}
                        </button>
                    </li>
                @empty
                    <li class="px-2 py-3 text-sm text-ink-muted">اختر مرحلة أو أضف صفًا.</li>
                @endforelse
            </ul>
            <form wire:submit="createGrade" class="space-y-2 border-t border-slate-100 pt-3">
                <x-text-input wire:model="gradeName" class="w-full" placeholder="اسم صف جديد" />
                <x-input-error :messages="$errors->get('gradeName')" />
                <x-input-error :messages="$errors->get('selectedStageId')" />
                <x-primary-button class="w-full justify-center">إضافة صف</x-primary-button>
            </form>
        </x-page-section>

        <x-page-section title="المواد">
            <ul class="mb-3 max-h-64 space-y-1 overflow-y-auto">
                @forelse ($subjects as $subject)
                    <li>
                        <button
                            type="button"
                            wire:click="selectSubject({{ $subject->id }})"
                            @class([
                                'w-full rounded-xl px-3 py-2 text-start text-sm transition',
                                'bg-brand-50 font-semibold text-brand-900' => $selectedSubjectId === $subject->id,
                                'text-ink hover:bg-slate-50' => $selectedSubjectId !== $subject->id,
                            ])
                        >
                            {{ $subject->name }}
                        </button>
                    </li>
                @empty
                    <li class="px-2 py-3 text-sm text-ink-muted">اختر صفًا أو أضف مادة.</li>
                @endforelse
            </ul>
            <form wire:submit="createSubject" class="space-y-2 border-t border-slate-100 pt-3">
                <x-text-input wire:model="subjectName" class="w-full" placeholder="اسم مادة جديدة" />
                <x-input-error :messages="$errors->get('subjectName')" />
                <x-input-error :messages="$errors->get('selectedGradeId')" />
                <x-primary-button class="w-full justify-center">إضافة مادة</x-primary-button>
            </form>
        </x-page-section>

        <x-page-section title="الوحدات">
            <ul class="mb-3 max-h-64 space-y-1 overflow-y-auto">
                @forelse ($units as $unit)
                    <li class="rounded-xl px-3 py-2 text-sm text-ink">{{ $unit->ordering }}. {{ $unit->name }}</li>
                @empty
                    <li class="px-2 py-3 text-sm text-ink-muted">اختر مادة أو أضف وحدة.</li>
                @endforelse
            </ul>
            <form wire:submit="createUnit" class="space-y-2 border-t border-slate-100 pt-3">
                <x-text-input wire:model="unitName" class="w-full" placeholder="اسم وحدة جديدة" />
                <x-input-error :messages="$errors->get('unitName')" />
                <x-input-error :messages="$errors->get('selectedSubjectId')" />
                <x-primary-button class="w-full justify-center">إضافة وحدة</x-primary-button>
            </form>
        </x-page-section>
    </div>
</div>
