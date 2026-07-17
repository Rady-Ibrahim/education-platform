<div class="space-y-8">
    @if (session('exam_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('exam_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <p class="text-sm text-gray-500">اربط مادة بحسابك من الإدارة أولاً.</p>
    @else
        <div>
            <x-input-label value="المادة" />
            <select wire:model.live="subjectId" class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm">
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="border rounded-lg p-4 space-y-4">
            <h3 class="font-medium">إضافة سؤال لبنك الأسئلة</h3>
            <form wire:submit="saveQuestion" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <x-input-label value="النوع" />
                        <select wire:model.live="questionType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="الدرجة" />
                        <x-text-input type="number" step="0.5" wire:model="points" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label value="نص السؤال" />
                    <textarea wire:model="questionStem" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    <x-input-error :messages="$errors->get('questionStem')" />
                </div>

                @if ($questionType === 'mcq')
                    <div class="space-y-2">
                        @foreach ($options as $index => $option)
                            <div class="flex gap-2 items-center">
                                <input type="radio" wire:model="correctOptionIndex" value="{{ $index }}" class="rounded-full border-gray-300">
                                <x-text-input wire:model="options.{{ $index }}.label" class="flex-1" placeholder="خيار {{ $index + 1 }}" />
                            </div>
                        @endforeach
                        <x-secondary-button type="button" wire:click="addOption">إضافة خيار</x-secondary-button>
                        <x-input-error :messages="$errors->get('options')" />
                    </div>
                @endif

                @if (in_array($questionType, ['true_false', 'fill_blank'], true))
                    <div>
                        <x-input-label value="{{ $questionType === 'true_false' ? 'الإجابة (true/false)' : 'الإجابة الصحيحة' }}" />
                        <x-text-input wire:model="correctAnswer" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('correctAnswer')" />
                    </div>
                @endif

                <x-primary-button>حفظ السؤال</x-primary-button>
            </form>
        </div>

        <div class="border rounded-lg p-4 space-y-4">
            <h3 class="font-medium">إنشاء امتحان</h3>
            <form wire:submit="createExam" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <x-input-label value="عنوان الامتحان" />
                        <x-text-input wire:model="examTitle" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('examTitle')" />
                    </div>
                    <div>
                        <x-input-label value="المدة (دقيقة)" />
                        <x-text-input type="number" wire:model="durationMinutes" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label value="اختر الأسئلة" />
                    <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border rounded-md p-2">
                        @forelse ($questions as $question)
                            <label class="flex gap-2 text-sm items-start">
                                <input type="checkbox" wire:model="selectedQuestionIds" value="{{ $question->id }}" class="rounded border-gray-300">
                                <span>{{ $question->type->label() }} — {{ \Illuminate\Support\Str::limit($question->stem, 80) }} ({{ $question->points }})</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">أضف أسئلة أولاً.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('selectedQuestionIds')" />
                </div>

                <x-primary-button>إنشاء ونشر الامتحان</x-primary-button>
            </form>
        </div>

        <div>
            <h3 class="font-medium mb-2">امتحانات المادة</h3>
            <ul class="space-y-2">
                @forelse ($exams as $exam)
                    <li class="border rounded-md px-3 py-2 text-sm">
                        {{ $exam->title }}
                        — {{ $exam->questions_count }} سؤال
                        — {{ $exam->duration_minutes }} د
                        @if ($exam->is_published)
                            <span class="text-green-600">منشور</span>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-gray-500">لا توجد امتحانات بعد.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
