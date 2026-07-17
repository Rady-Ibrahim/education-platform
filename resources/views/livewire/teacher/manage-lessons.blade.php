<div class="space-y-6">
    @if (session('lesson_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('lesson_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <p class="text-sm text-gray-500">لا توجد مواد مربوطة بحسابك. تواصل مع الإدارة أولاً.</p>
    @else
        <form wire:submit="save" class="space-y-4 border rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model.live="subjectId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="الوحدة" />
                    <select wire:model="unitId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unitId')" />
                </div>
            </div>

            <div>
                <x-input-label value="عنوان الدرس" />
                <x-text-input wire:model="title" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="النوع" />
                    <select wire:model.live="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($types as $lessonType)
                            <option value="{{ $lessonType->value }}">{{ $lessonType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if (in_array($type, ['video', 'mixed'], true))
                    <div>
                        <x-input-label value="Bunny Video ID" />
                        <x-text-input wire:model="bunnyVideoId" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('bunnyVideoId')" />
                        <x-input-error :messages="$errors->get('bunny_video_id')" />
                    </div>
                @endif
            </div>

            <div>
                <x-input-label value="المحتوى النصي" />
                <textarea wire:model="body" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="isPublished" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                نشر مباشرة
            </label>

            <div>
                <x-primary-button>حفظ الدرس</x-primary-button>
            </div>
        </form>

        <div class="space-y-2">
            <h4 class="font-medium">دروس الوحدة المختارة</h4>
            @forelse ($lessons as $lesson)
                <div class="border rounded-md px-3 py-2 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div class="text-sm">
                        <span class="font-medium">{{ $lesson->title }}</span>
                        <span class="text-gray-500">— {{ $lesson->type->label() }}</span>
                        @if ($lesson->is_published)
                            <span class="text-green-600">منشور</span>
                        @else
                            <span class="text-amber-600">مسودة</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <x-secondary-button wire:click="togglePublish({{ $lesson->id }})">
                            {{ $lesson->is_published ? 'إلغاء النشر' : 'نشر' }}
                        </x-secondary-button>
                        <x-danger-button wire:click="deleteLesson({{ $lesson->id }})">حذف</x-danger-button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد دروس في هذه الوحدة بعد.</p>
            @endforelse
        </div>
    @endif
</div>
