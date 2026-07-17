<div class="space-y-6">
    @if (session('exam_take_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('exam_take_status') }}
            @if (!is_null($resultScore))
                — الدرجة: {{ $resultScore }} / {{ $resultMax }}
            @endif
        </div>
    @endif

    @if (!$attemptId)
        <div class="space-y-2">
            <h3 class="font-medium">الامتحانات المتاحة</h3>
            @forelse ($availableExams as $exam)
                <div class="border rounded-md px-3 py-2 flex items-center justify-between gap-3">
                    <div class="text-sm">
                        <div class="font-medium">{{ $exam->title }}</div>
                        <div class="text-gray-500">
                            {{ $exam->subject?->name }}
                            — {{ $exam->duration_minutes }} دقيقة
                            — {{ $exam->max_attempts }} محاولة
                        </div>
                    </div>
                    <x-primary-button wire:click="startExam({{ $exam->id }})">بدء</x-primary-button>
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد امتحانات متاحة الآن.</p>
            @endforelse
        </div>
    @else
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-medium">الامتحان جارٍ — الحفظ تلقائي عند كل إجابة</h3>
                <x-primary-button wire:click="submit" wire:confirm="تسليم الامتحان الآن؟">تسليم</x-primary-button>
            </div>

            @foreach ($questions as $index => $question)
                @php
                    $saved = $savedAnswers[$question['id']] ?? null;
                @endphp
                <div class="border rounded-lg p-4 space-y-3" wire:key="q-{{ $question['id'] }}">
                    <div class="text-sm font-medium">
                        {{ $index + 1 }}. {{ $question['stem'] }}
                        <span class="text-gray-400">({{ $question['points'] }} درجة)</span>
                    </div>

                    @if ($question['type'] === 'mcq' || $question['type'] === 'true_false')
                        <div class="space-y-2">
                            @foreach ($question['options'] as $option)
                                <label class="flex gap-2 items-center text-sm">
                                    <input
                                        type="radio"
                                        name="q-{{ $question['id'] }}"
                                        value="{{ $option['id'] }}"
                                        @checked($saved?->selected_option_id === $option['id'])
                                        wire:change="saveOption({{ $question['id'] }}, {{ $option['id'] }})"
                                        class="border-gray-300"
                                    >
                                    <span>{{ $option['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <textarea
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                            rows="3"
                            wire:blur="saveText({{ $question['id'] }}, $event.target.value)"
                        >{{ $saved?->answer_text }}</textarea>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
