<div class="space-y-5">
    @if (session('exam_status'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('exam_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($subjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center text-sm text-ink-muted shadow-soft">
            حدّد مادتك أولًا من
            <a href="{{ route('profile') }}" class="link-brand">البروفايل ↗</a>.
        </div>
    @else
        <x-page-section title="المادة" subtitle="كل الأسئلة والامتحانات مربوطة بالمادة المختارة.">
            <div class="max-w-xl">
                <x-input-label value="اختر المادة" />
                <select wire:model.live="subjectId" class="mt-1.5 block w-full">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-page-section>

        <div class="grid gap-5 xl:grid-cols-2">
            {{-- ===== بنك الأسئلة ===== --}}
            <x-page-section
                :title="$editingQuestionId ? 'تعديل سؤال' : 'إضافة سؤال لبنك الأسئلة'"
                subtitle="MCQ · صح/خطأ · فراغ · مقال."
            >
                <form wire:submit="{{ $editingQuestionId ? 'updateQuestion' : 'saveQuestion' }}" class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label value="النوع" />
                            <select wire:model.live="questionType" class="mt-1.5 block w-full" @disabled((bool) $editingQuestionId)>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="الدرجة" />
                            <x-text-input type="number" step="0.5" wire:model="points" class="mt-1.5 block w-full" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="نص السؤال" />
                        <textarea wire:model="questionStem" rows="3" class="mt-1.5 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
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
                            <p class="text-xs text-ink-muted">اختر الراديو أمام الإجابة الصحيحة.</p>
                            <x-input-error :messages="$errors->get('options')" />
                        </div>
                    @endif

                    @if (in_array($questionType, ['true_false', 'fill_blank'], true))
                        <div>
                            <x-input-label value="{{ $questionType === 'true_false' ? 'الإجابة (true/false)' : 'الإجابة الصحيحة' }}" />
                            <x-text-input wire:model="correctAnswer" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('correctAnswer')" />
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @if ($editingQuestionId)
                            <x-primary-button>حفظ التعديلات</x-primary-button>
                            <x-secondary-button type="button" wire:click="cancelQuestionEdit">إلغاء</x-secondary-button>
                        @else
                            <x-primary-button>حفظ السؤال</x-primary-button>
                        @endif
                    </div>
                </form>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <h4 class="mb-2 text-sm font-bold text-ink">بنك الأسئلة ({{ $questions->count() }})</h4>
                    <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                        @forelse ($questions as $question)
                            <div class="list-row !py-2" wire:key="bank-{{ $question->id }}">
                                <div class="min-w-0 text-xs">
                                    <span class="font-semibold text-brand-700">{{ $question->type->label() }}</span>
                                    — <span class="text-ink">{{ \Illuminate\Support\Str::limit($question->stem, 60) }}</span>
                                    ({{ $question->points }} درجة، مرفق بـ{{ $question->exams_count }} امتحان)
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" wire:click="loadQuestion({{ $question->id }})" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">تعديل</button>
                                    @if ($question->exams_count === 0)
                                        <button type="button" wire:click="deleteQuestion({{ $question->id }})" wire:confirm="حذف السؤال من البنك؟" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">حذف</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">لا توجد أسئلة بعد.</p>
                        @endforelse
                    </div>
                </div>
            </x-page-section>

            {{-- ===== إنشاء / تعديل امتحان ===== --}}
            <x-page-section
                :title="$editingExamId ? 'تعديل الامتحان' : 'إنشاء امتحان'"
                subtitle="اختر الأسئلة من البنك ثم انشر أو احفظ كمسودة."
            >
                <form wire:submit="{{ $editingExamId ? 'updateExam' : 'createExam' }}" class="space-y-3">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <x-input-label value="عنوان الامتحان" />
                            <x-text-input wire:model="examTitle" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('examTitle')" />
                        </div>
                        <div>
                            <x-input-label value="المدة (دقيقة)" />
                            <x-text-input type="number" wire:model="durationMinutes" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('durationMinutes')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="الوصف (اختياري)" />
                        <textarea wire:model="examDescription" rows="2" class="mt-1.5 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <x-input-label value="يبدأ في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="startsAt" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('startsAt')" />
                        </div>
                        <div>
                            <x-input-label value="ينتهي في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="endsAt" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('endsAt')" />
                        </div>
                        <div>
                            <x-input-label value="درجة النجاح % (اختياري)" />
                            <x-text-input type="number" step="0.5" wire:model="passScore" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('passScore')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <x-input-label value="عدد المحاولات" />
                            <x-text-input type="number" wire:model="maxAttempts" min="1" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('maxAttempts')" />
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="publishNow" class="rounded border-slate-300 text-brand-700">
                                <span>{{ $editingExamId ? 'منشور' : 'نشر فورًا' }}</span>
                            </label>
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

                    <div class="flex flex-wrap gap-2">
                        @if ($editingExamId)
                            <x-primary-button>حفظ التعديلات</x-primary-button>
                            <x-secondary-button type="button" wire:click="cancelExamEdit">إلغاء</x-secondary-button>
                        @else
                            <x-primary-button>{{ $publishNow ? 'إنشاء ونشر الامتحان' : 'حفظ كمسودة' }}</x-primary-button>
                        @endif
                    </div>
                </form>
            </x-page-section>
        </div>

        {{-- ===== جدول الامتحانات ===== --}}
        <x-page-section title="امتحانات المادة" subtitle="نشر / إلغاء نشر · تعديل · نتائج وإحصائيات · حذف.">
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الأسئلة</th>
                            <th>المدة</th>
                            <th>المحاولات</th>
                            <th>النافذة</th>
                            <th>الحالة</th>
                            <th class="text-left">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exams as $exam)
                            <tr wire:key="exam-{{ $exam->id }}">
                                <td class="font-semibold">{{ $exam->title }}</td>
                                <td>{{ $exam->questions_count }}</td>
                                <td>{{ $exam->duration_minutes ?? '—' }} د</td>
                                <td>{{ $exam->max_attempts }}</td>
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
                                <td>
                                    <div class="flex flex-wrap justify-start gap-1">
                                        <a href="{{ route('teacher.exams.results', $exam) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">النتائج</a>
                                        <button type="button" wire:click="togglePublish({{ $exam->id }})" class="rounded-lg px-2 py-1 text-xs font-semibold {{ $exam->is_published ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                            {{ $exam->is_published ? 'إلغاء النشر' : 'نشر' }}
                                        </button>
                                        <button type="button" wire:click="loadExam({{ $exam->id }})" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">تعديل</button>
                                        <button type="button" wire:click="deleteExam({{ $exam->id }})" wire:confirm="حذف الامتحان؟ سيتم تسليم المحاولات الجارية تلقائيًا." class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">حذف</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-ink-muted">لا توجد امتحانات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-page-section>
    @endif
</div>
