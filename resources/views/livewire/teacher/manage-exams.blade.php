<div>
    @if (session('exam_status'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('exam_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-10 text-center text-sm text-ink-muted">
            حدّد مادتك أولًا من
            <a href="{{ route('profile') }}" class="link-brand" wire:navigate>البروفايل</a>.
        </div>
    @else
        <div class="mb-6 max-w-xl">
            <x-input-label value="المادة" />
            <select wire:model.live="subjectId" class="mt-1 block w-full">
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 p-5">
                <h3 class="text-base font-bold text-ink">إضافة سؤال لبنك الأسئلة</h3>
                <form wire:submit="saveQuestion" class="mt-4 space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label value="النوع" />
                            <select wire:model.live="questionType" class="mt-1 block w-full">
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
                        <textarea wire:model="questionStem" rows="3" class="mt-1 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        <x-input-error :messages="$errors->get('questionStem')" />
                    </div>

                    @if ($questionType === 'mcq')
                        <div class="space-y-2">
                            @foreach ($options as $index => $option)
                                <div class="flex items-center gap-2">
                                    <input type="radio" wire:model="correctOptionIndex" value="{{ $index }}" class="border-slate-300 text-brand-700">
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
            </section>

            <section class="rounded-2xl border border-slate-200 p-5">
                <h3 class="text-base font-bold text-ink">إنشاء امتحان</h3>
                <form wire:submit="createExam" class="mt-4 space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
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

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <x-input-label value="يبدأ في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="startsAt" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('startsAt')" />
                        </div>
                        <div>
                            <x-input-label value="ينتهي في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="endsAt" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('endsAt')" />
                        </div>
                        <div>
                            <x-input-label value="درجة النجاح % (اختياري)" />
                            <x-text-input type="number" step="0.5" wire:model="passScore" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('passScore')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="اختر الأسئلة" />
                        <div class="mt-2 max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                            @forelse ($questions as $question)
                                <label class="flex items-start gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                                    <input type="checkbox" wire:model="selectedQuestionIds" value="{{ $question->id }}" class="mt-0.5 rounded border-slate-300 text-brand-700">
                                    <span>{{ $question->type->label() }} — {{ \Illuminate\Support\Str::limit($question->stem, 80) }} ({{ $question->points }})</span>
                                </label>
                            @empty
                                <p class="text-sm text-ink-muted">أضف أسئلة أولاً.</p>
                            @endforelse
                        </div>
                        <x-input-error :messages="$errors->get('selectedQuestionIds')" />
                    </div>

                    <x-primary-button>إنشاء ونشر الامتحان</x-primary-button>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-2xl border border-slate-200 p-5">
            <h3 class="mb-3 text-base font-bold text-ink">امتحانات المادة</h3>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الأسئلة</th>
                            <th>المدة</th>
                            <th>النافذة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exams as $exam)
                            <tr>
                                <td class="font-semibold">{{ $exam->title }}</td>
                                <td>{{ $exam->questions_count }}</td>
                                <td>{{ $exam->duration_minutes }} د</td>
                                <td class="text-xs text-ink-muted">
                                    @if ($exam->starts_at || $exam->ends_at)
                                        {{ $exam->starts_at?->format('m-d H:i') ?? '—' }}
                                        →
                                        {{ $exam->ends_at?->format('m-d H:i') ?? '—' }}
                                    @else
                                        مفتوحة
                                    @endif
                                </td>
                                <td>
                                    @if ($exam->is_published)
                                        <x-status-badge tone="success">منشور</x-status-badge>
                                    @else
                                        <x-status-badge tone="neutral">مسودة</x-status-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-ink-muted">لا توجد امتحانات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
