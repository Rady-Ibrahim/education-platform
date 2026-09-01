<div class="space-y-5" x-data="{ examTab: 'list' }">

    {{-- Flash --}}
    @if (session('exam_status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('exam_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @if ($subjects->isEmpty())
        <div class="empty-state py-16">
            <p class="text-sm text-ink-muted">حدّد مادتك أولًا من <a href="{{ route('profile') }}" wire:navigate class="link-brand font-semibold">البروفايل ↗</a>.</p>
        </div>
    @else

        {{-- Subject selector --}}
        <x-page-section>
            <div class="max-w-sm">
                <x-input-label value="المادة" />
                <select wire:model.live="subjectId" class="mt-1.5 block w-full">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-page-section>

        {{-- Tab bar --}}
        <div class="page-tabs" role="tablist">
            <button type="button" role="tab"
                :class="examTab === 'list' ? 'page-tab-active' : 'page-tab'"
                @click="examTab = 'list'">
                الامتحانات
                @if ($exams->count() > 0)
                    <span :class="examTab==='list' ? 'page-tab-badge-active page-tab-badge' : 'page-tab-badge'">{{ $exams->count() }}</span>
                @endif
            </button>
            <button type="button" role="tab"
                :class="examTab === 'create' ? 'page-tab-active' : 'page-tab'"
                @click="examTab = 'create'; $wire.cancelExamEdit()">
                {{ $editingExamId ? 'تعديل الامتحان' : 'إنشاء امتحان' }}
            </button>
            <button type="button" role="tab"
                :class="examTab === 'bank' ? 'page-tab-active' : 'page-tab'"
                @click="examTab = 'bank'">
                بنك الأسئلة
                @if ($questions->count() > 0)
                    <span :class="examTab==='bank' ? 'page-tab-badge-active page-tab-badge' : 'page-tab-badge'">{{ $questions->count() }}</span>
                @endif
            </button>
        </div>

        {{-- ══════════════ TAB: Exams List ══════════════ --}}
        <div x-show="examTab === 'list'" x-cloak>
            <x-page-section title="امتحانات المادة" subtitle="نشر · تعديل · نتائج · حذف.">
                <div class="mb-4 flex justify-end">
                    <button type="button" @click="examTab = 'create'; $wire.cancelExamEdit()"
                        class="btn-brand">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        إنشاء امتحان جديد
                    </button>
                </div>

                @if ($exams->isEmpty())
                    <div class="empty-state py-12">
                        <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <p class="text-sm text-ink-muted">لا توجد امتحانات بعد.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($exams as $exam)
                            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-soft transition hover:border-brand-200"
                                 wire:key="exam-{{ $exam->id }}">
                                <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-ink">{{ $exam->title }}</span>
                                            @if ($exam->is_published)
                                                <x-status-badge tone="success">منشور</x-status-badge>
                                            @else
                                                <x-status-badge tone="neutral">مسودة</x-status-badge>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-3 text-xs text-ink-muted">
                                            <span>{{ $exam->questions_count }} سؤال</span>
                                            <span>{{ $exam->duration_minutes ?? '—' }} دقيقة</span>
                                            <span>{{ $exam->attempts_count }} محاولة</span>
                                            @if ($exam->starts_at || $exam->ends_at)
                                                <span>{{ $exam->starts_at?->format('d/m H:i') ?? '—' }} ← {{ $exam->ends_at?->format('d/m H:i') ?? '—' }}</span>
                                            @else
                                                <span>نافذة مفتوحة</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        <a href="{{ route('teacher.exams.results', $exam) }}" wire:navigate
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-brand-700 transition hover:border-brand-200 hover:bg-brand-50">
                                            النتائج
                                        </a>
                                        <button type="button"
                                            wire:click="loadExam({{ $exam->id }})"
                                            @click="examTab = 'create'"
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-brand-200 hover:bg-brand-50">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            تعديل
                                        </button>
                                        <button type="button" wire:click="togglePublish({{ $exam->id }})"
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold transition
                                                   {{ $exam->is_published ? 'text-amber-600 hover:border-amber-200 hover:bg-amber-50' : 'text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50' }}">
                                            {{ $exam->is_published ? 'إلغاء النشر' : 'نشر' }}
                                        </button>
                                        <button type="button"
                                            wire:click="deleteExam({{ $exam->id }})"
                                            wire:confirm="حذف الامتحان '{{ $exam->title }}'؟ سيتم تسليم المحاولات الجارية."
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 transition hover:border-rose-200 hover:bg-rose-50">
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-page-section>
        </div>

        {{-- ══════════════ TAB: Create / Edit Exam ══════════════ --}}
        <div x-show="examTab === 'create'" x-cloak>
            <x-page-section
                :title="$editingExamId ? 'تعديل الامتحان' : 'إنشاء امتحان جديد'"
                subtitle="اضبط الإعدادات، ثم اختر الأسئلة من البنك.">

                <form wire:submit="{{ $editingExamId ? 'updateExam' : 'createExam' }}" class="space-y-5">

                    {{-- Basic info --}}
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-input-label value="عنوان الامتحان" />
                            <x-text-input wire:model="examTitle" class="mt-1.5 block w-full" placeholder="مثلاً: امتحان الفصل الأول" />
                            <x-input-error :messages="$errors->get('examTitle')" />
                        </div>
                        <div>
                            <x-input-label value="المدة (دقيقة)" />
                            <x-text-input type="number" wire:model="durationMinutes" min="1" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('durationMinutes')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="الوصف (اختياري)" />
                        <textarea wire:model="examDescription" rows="2" class="mt-1.5 block w-full" placeholder="تعليمات للطالب…"></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <x-input-label value="عدد المحاولات" />
                            <x-text-input type="number" wire:model="maxAttempts" min="1" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('maxAttempts')" />
                        </div>
                        <div>
                            <x-input-label value="درجة النجاح % (اختياري)" />
                            <x-text-input type="number" step="0.5" wire:model="passScore" class="mt-1.5 block w-full" />
                            <x-input-error :messages="$errors->get('passScore')" />
                        </div>
                        <div>
                            <x-input-label value="يبدأ في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="startsAt" class="mt-1.5 block w-full" />
                        </div>
                        <div>
                            <x-input-label value="ينتهي في (اختياري)" />
                            <x-text-input type="datetime-local" wire:model="endsAt" class="mt-1.5 block w-full" />
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="flex flex-wrap gap-5 rounded-2xl border border-slate-200/80 bg-slate-50/60 px-4 py-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="shuffleQuestions" class="rounded border-slate-300 text-brand-700">
                            <span class="font-medium text-ink">ترتيب عشوائي للأسئلة</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="publishNow" class="rounded border-slate-300 text-brand-700">
                            <span class="font-medium text-ink">{{ $editingExamId ? 'منشور' : 'نشر فورًا' }}</span>
                        </label>
                    </div>

                    {{-- Question picker --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <x-input-label value="اختر الأسئلة من البنك" />
                            <span class="text-xs text-ink-muted">{{ count($selectedQuestionIds) }} مختار</span>
                        </div>

                        @if ($questions->isEmpty())
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-8 text-center text-sm text-ink-muted">
                                لا توجد أسئلة في البنك لهذه المادة.
                                <button type="button" @click="examTab = 'bank'"
                                    class="ms-1 font-semibold text-brand-700 hover:underline underline-offset-4">
                                    اذهب لبنك الأسئلة ↗
                                </button>
                            </div>
                        @else
                            <div class="space-y-2 rounded-2xl border border-slate-200/80 bg-white p-3">
                                {{-- Search filter (Alpine) --}}
                                <div x-data="{ q: '' }" class="space-y-2">
                                    <input type="text" x-model="q" placeholder="بحث في الأسئلة…"
                                        class="block w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                                    <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                                        @foreach ($questions as $question)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-slate-50"
                                                x-show="q === '' || '{{ addslashes($question->stem) }}'.toLowerCase().includes(q.toLowerCase())"
                                                wire:key="qpick-{{ $question->id }}">
                                                <input type="checkbox" wire:model="selectedQuestionIds" value="{{ $question->id }}"
                                                    class="mt-0.5 rounded border-slate-300 text-brand-700 shrink-0">
                                                <span class="min-w-0">
                                                    <span class="rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-semibold text-brand-800">{{ $question->type->label() }}</span>
                                                    <span class="ms-1.5 text-ink">{{ \Illuminate\Support\Str::limit($question->stem, 100) }}</span>
                                                    <span class="ms-1 text-xs text-ink-muted">({{ $question->points }} درجة)</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('selectedQuestionIds')" />
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                        @if ($editingExamId)
                            <x-secondary-button type="button" wire:click="cancelExamEdit" @click="examTab = 'list'">إلغاء</x-secondary-button>
                            <x-primary-button>حفظ التعديلات</x-primary-button>
                        @else
                            <x-primary-button>
                                {{ $publishNow ? 'إنشاء ونشر الامتحان' : 'حفظ كمسودة' }}
                            </x-primary-button>
                        @endif
                    </div>
                </form>
            </x-page-section>
        </div>

        {{-- ══════════════ TAB: Question Bank ══════════════ --}}
        <div x-show="examTab === 'bank'" x-cloak>
            <div class="grid gap-5 xl:grid-cols-[1fr_1.1fr]">

                {{-- Add / Edit question form --}}
                <x-page-section
                    :title="$editingQuestionId ? 'تعديل السؤال' : 'إضافة سؤال'"
                    subtitle="MCQ · صح/خطأ · فراغ · مقال.">

                    <form wire:submit="{{ $editingQuestionId ? 'updateQuestion' : 'saveQuestion' }}" class="space-y-4">

                        <div class="grid gap-3 sm:grid-cols-2">
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
                                <x-text-input type="number" step="0.5" min="0.5" wire:model="points" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('points')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label value="نص السؤال" />
                            <textarea wire:model="questionStem" rows="3" class="mt-1.5 block w-full" placeholder="اكتب السؤال هنا…"></textarea>
                            <x-input-error :messages="$errors->get('questionStem')" />
                        </div>

                        @if ($questionType === 'mcq')
                            <div class="space-y-2.5">
                                <x-input-label value="الخيارات (اختر الصحيح بالراديو)" />
                                @foreach ($options as $index => $option)
                                    <div class="flex items-center gap-2.5" wire:key="opt-{{ $index }}">
                                        <input type="radio" wire:model="correctOptionIndex" value="{{ $index }}"
                                            class="h-4 w-4 border-slate-300 text-brand-700 shrink-0">
                                        <x-text-input wire:model="options.{{ $index }}.label" class="flex-1"
                                            placeholder="الخيار {{ $index + 1 }}" />
                                    </div>
                                @endforeach
                                <x-secondary-button type="button" wire:click="addOption">+ خيار</x-secondary-button>
                                <x-input-error :messages="$errors->get('options')" />
                            </div>
                        @endif

                        @if (in_array($questionType, ['true_false', 'fill_blank'], true))
                            <div>
                                <x-input-label value="{{ $questionType === 'true_false' ? 'الإجابة (true أو false)' : 'الإجابة الصحيحة' }}" />
                                <x-text-input wire:model="correctAnswer" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('correctAnswer')" />
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                            @if ($editingQuestionId)
                                <x-primary-button>حفظ التعديلات</x-primary-button>
                                <x-secondary-button type="button" wire:click="cancelQuestionEdit">إلغاء</x-secondary-button>
                            @else
                                <x-primary-button>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    حفظ في البنك
                                </x-primary-button>
                            @endif
                        </div>
                    </form>
                </x-page-section>

                {{-- Questions list --}}
                <x-page-section title="أسئلة البنك" :subtitle="$questions->count().' سؤال مضاف'">
                    @if ($questions->isEmpty())
                        <div class="empty-state py-10">
                            <p class="text-sm text-ink-muted">لا توجد أسئلة في البنك بعد.</p>
                        </div>
                    @else
                        <div class="space-y-2" x-data="{ q: '' }">
                            <input type="text" x-model="q" placeholder="بحث…"
                                class="block w-full rounded-xl border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">

                            <div class="space-y-2">
                                @foreach ($questions as $question)
                                    <div class="group rounded-xl border border-slate-200/80 bg-white p-3 transition hover:border-brand-200 hover:shadow-soft"
                                         wire:key="bank-{{ $question->id }}"
                                         x-show="q === '' || '{{ addslashes($question->stem) }}'.toLowerCase().includes(q.toLowerCase())">
                                        <div class="flex items-start gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-md bg-brand-50 px-1.5 py-0.5 text-[11px] font-bold text-brand-800">{{ $question->type->label() }}</span>
                                                    <span class="text-xs text-ink-muted">{{ $question->points }} درجة</span>
                                                    @if ($question->exams_count > 0)
                                                        <span class="text-xs text-ink-muted">· {{ $question->exams_count }} امتحان</span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Str::limit($question->stem, 120) }}</p>
                                            </div>
                                            <div class="flex shrink-0 gap-1 opacity-0 transition group-hover:opacity-100">
                                                <button type="button" wire:click="loadQuestion({{ $question->id }})"
                                                    class="rounded-lg px-2 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">تعديل</button>
                                                @if ($question->exams_count === 0)
                                                    <button type="button"
                                                        wire:click="deleteQuestion({{ $question->id }})"
                                                        wire:confirm="حذف هذا السؤال من البنك؟"
                                                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">حذف</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-page-section>
            </div>
        </div>

    @endif
</div>
