<div class="space-y-6">
    @if (session('academic_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('academic_status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Stages --}}
        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">المراحل</h4>
            <ul class="space-y-2 max-h-64 overflow-y-auto">
                @foreach ($stages as $stage)
                    <li>
                        <button
                            type="button"
                            wire:click="selectStage({{ $stage->id }})"
                            class="w-full text-start px-3 py-2 rounded-md text-sm {{ $selectedStageId === $stage->id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}"
                        >
                            {{ $stage->name }}
                            @unless ($stage->is_active)
                                <span class="text-xs text-red-500">(موقوف)</span>
                            @endunless
                        </button>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="createStage" class="space-y-2">
                <x-text-input wire:model="stageName" class="w-full" placeholder="اسم مرحلة جديدة" />
                <x-input-error :messages="$errors->get('stageName')" />
                <x-primary-button class="w-full justify-center">إضافة مرحلة</x-primary-button>
            </form>
        </div>

        {{-- Grades --}}
        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">الصفوف</h4>
            <ul class="space-y-2 max-h-64 overflow-y-auto">
                @forelse ($grades as $grade)
                    <li>
                        <button
                            type="button"
                            wire:click="selectGrade({{ $grade->id }})"
                            class="w-full text-start px-3 py-2 rounded-md text-sm {{ $selectedGradeId === $grade->id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}"
                        >
                            {{ $grade->name }}
                        </button>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">اختر مرحلة أو أضف صفًا.</li>
                @endforelse
            </ul>
            <form wire:submit="createGrade" class="space-y-2">
                <x-text-input wire:model="gradeName" class="w-full" placeholder="اسم صف جديد" />
                <x-input-error :messages="$errors->get('gradeName')" />
                <x-input-error :messages="$errors->get('selectedStageId')" />
                <x-primary-button class="w-full justify-center">إضافة صف</x-primary-button>
            </form>
        </div>

        {{-- Subjects --}}
        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">المواد</h4>
            <ul class="space-y-2 max-h-64 overflow-y-auto">
                @forelse ($subjects as $subject)
                    <li>
                        <button
                            type="button"
                            wire:click="selectSubject({{ $subject->id }})"
                            class="w-full text-start px-3 py-2 rounded-md text-sm {{ $selectedSubjectId === $subject->id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}"
                        >
                            {{ $subject->name }}
                        </button>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">اختر صفًا أو أضف مادة.</li>
                @endforelse
            </ul>
            <form wire:submit="createSubject" class="space-y-2">
                <x-text-input wire:model="subjectName" class="w-full" placeholder="اسم مادة جديدة" />
                <x-input-error :messages="$errors->get('subjectName')" />
                <x-input-error :messages="$errors->get('selectedGradeId')" />
                <x-primary-button class="w-full justify-center">إضافة مادة</x-primary-button>
            </form>
        </div>

        {{-- Units --}}
        <div class="border rounded-lg p-4 space-y-3">
            <h4 class="font-medium">الوحدات</h4>
            <ul class="space-y-2 max-h-64 overflow-y-auto">
                @forelse ($units as $unit)
                    <li class="px-3 py-2 text-sm text-gray-700">{{ $unit->ordering }}. {{ $unit->name }}</li>
                @empty
                    <li class="text-sm text-gray-500">اختر مادة أو أضف وحدة.</li>
                @endforelse
            </ul>
            <form wire:submit="createUnit" class="space-y-2">
                <x-text-input wire:model="unitName" class="w-full" placeholder="اسم وحدة جديدة" />
                <x-input-error :messages="$errors->get('unitName')" />
                <x-input-error :messages="$errors->get('selectedSubjectId')" />
                <x-primary-button class="w-full justify-center">إضافة وحدة</x-primary-button>
            </form>
        </div>
    </div>
</div>
