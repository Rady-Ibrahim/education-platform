<div class="space-y-5">
    @if (session('exam_take_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('exam_take_status') }}
            @if (! is_null($resultScore))
                — الدرجة: {{ $resultScore }} / {{ $resultMax }}
            @endif
        </div>
    @endif

    @if (! $attemptId)
        <x-page-section title="الامتحانات المتاحة" subtitle="ابدأ المحاولة — المدة وعدد المحاولات ظاهرين لكل امتحان.">
            <div class="space-y-3">
                @forelse ($availableExams as $exam)
                    <div class="list-row">
                        <div class="text-sm">
                            <div class="font-semibold text-ink">{{ $exam->title }}</div>
                            <div class="text-ink-muted">
                                {{ $exam->subject?->name }}
                                — {{ $exam->duration_minutes }} دقيقة
                                — {{ $exam->max_attempts }} محاولة
                            </div>
                        </div>
                        <x-primary-button wire:click="startExam({{ $exam->id }})">بدء</x-primary-button>
                    </div>
                @empty
                    <div class="empty-state">
                        <p class="text-sm text-ink-muted">لا توجد امتحانات متاحة الآن.</p>
                    </div>
                @endforelse
            </div>
        </x-page-section>
    @else
        <x-page-section title="الامتحان جارٍ" subtitle="الحفظ تلقائي عند كل إجابة.">
            <x-slot:actions>
                <x-primary-button wire:click="submit" wire:confirm="تسليم الامتحان الآن؟">تسليم</x-primary-button>
            </x-slot:actions>

            <div class="space-y-3">
                @foreach ($questions as $index => $question)
                    @php
                        $saved = $savedAnswers[$question['id']] ?? null;
                    @endphp
                    <div class="list-row !items-stretch !flex-col space-y-3" wire:key="q-{{ $question['id'] }}">
                        <div class="text-sm font-semibold text-ink">
                            {{ $index + 1 }}. {{ $question['stem'] }}
                            <span class="font-normal text-ink-muted">({{ $question['points'] }} درجة)</span>
                        </div>

                        @if ($question['type'] === 'mcq' || $question['type'] === 'true_false')
                            <div class="space-y-2">
                                @foreach ($question['options'] as $option)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="q-{{ $question['id'] }}"
                                            value="{{ $option['id'] }}"
                                            @checked($saved?->selected_option_id === $option['id'])
                                            wire:change="saveOption({{ $question['id'] }}, {{ $option['id'] }})"
                                            class="border-slate-300 text-brand-700"
                                        >
                                        <span>{{ $option['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <textarea
                                class="w-full rounded-xl border-slate-200 text-sm shadow-sm"
                                rows="3"
                                wire:blur="saveText({{ $question['id'] }}, $event.target.value)"
                            >{{ $saved?->answer_text }}</textarea>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-page-section>
    @endif
</div>
