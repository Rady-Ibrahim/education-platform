<div class="space-y-5" x-data="{ timerFired: false }">
    @if (session('exam_take_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('exam_take_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ===== النتيجة ===== --}}
    @if (! is_null($resultScore))
        <x-page-section title="نتيجة الامتحان" subtitle="تم تسليم إجاباتك واحتساب الدرجة.">            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="surface-stat">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">الدرجة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ number_format($resultScore, 1) }} <span class="text-base font-medium text-ink-muted">/ {{ number_format($resultMax, 1) }}</span></div>
                </div>
                <div class="surface-stat">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">النسبة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ number_format($resultPercent, 1) }}%</div>
                </div>
                <div class="surface-stat">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">إجابات صحيحة / خاطئة</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $resultCorrect }} <span class="text-ink-muted">/</span> <span class="text-rose-500">{{ $resultWrong }}</span></div>
                </div>
                <div class="surface-stat flex flex-col justify-between gap-2">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">الحالة</div>
                        <div class="mt-2">
                            @if (! is_null($resultPassed))
                                @if ($resultPassed)
                                    <x-status-badge tone="success">ناجح</x-status-badge>
                                @else
                                    <x-status-badge tone="danger">رسوب</x-status-badge>
                                @endif
                            @else
                                <x-status-badge tone="neutral">مُسلَّم</x-status-badge>
                            @endif
                        </div>
                    </div>
                    <div class="text-xs text-ink-muted">بدون إجابة: {{ $resultUnanswered }} من {{ $resultTotal }}</div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-secondary-button wire:click="backToList">عودة لقائمة الامتحانات</x-secondary-button>
            </div>
        </x-page-section>
    @endif

    {{-- ===== الامتحان جارٍ ===== --}}
    @if (is_null($resultScore) && $attemptId && $currentQuestion)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-bold text-ink">السؤال {{ $currentIndex + 1 }} من {{ count($questions) }}</div>
                    <div class="mt-0.5 text-xs text-ink-muted">الحفظ تلقائي عند كل إجابة</div>
                </div>

                @if ($hasTimer)
                    <div
                        x-data="{
                            remaining: {{ $remainingSeconds }},
                            display: '',
                            init() {
                                const tick = () => {
                                    const m = Math.floor(this.remaining / 60);
                                    const s = this.remaining % 60;
                                    this.display = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                };
                                tick();
                                setInterval(() => {
                                    if (this.remaining > 0) {
                                        this.remaining--;
                                        tick();
                                    }
                                    if (this.remaining === 0 && ! timerFired) {
                                        timerFired = true;
                                        $wire.autoSubmit();
                                    }
                                }, 1000);
                            }
                        }"
                        x-init="init()"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 ring-1"
                        :class="remaining <= 60 ? 'bg-rose-50 text-rose-700 ring-rose-200' : (remaining <= 300 ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-brand-50 text-brand-800 ring-brand-100')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-mono text-lg font-bold tabular-nums" x-text="display"></span>
                    </div>
                @endif
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full bg-brand-600 transition-all duration-300"
                    style="width: {{ count($questions) > 0 ? round((($currentIndex + 1) / count($questions)) * 100) : 0 }}%"
                ></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft" wire:key="question-{{ $currentQuestion['id'] }}">
            <div class="text-base font-semibold leading-relaxed text-ink">
                {{ $currentQuestion['stem'] }}
            </div>
            <div class="mt-1 text-xs text-ink-muted">({{ $currentQuestion['points'] }} درجة)</div>

            @if (in_array($currentQuestion['type'], ['mcq', 'true_false'], true))
                <div class="mt-4 space-y-2">
                    @foreach ($currentQuestion['options'] as $option)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-brand-300 hover:bg-brand-50/40 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input
                                type="radio"
                                name="q-{{ $currentQuestion['id'] }}"
                                value="{{ $option['id'] }}"
                                @checked(($answers[$currentQuestion['id']]['option_id'] ?? null) === $option['id'])
                                wire:change="saveOption({{ $currentQuestion['id'] }}, {{ $option['id'] }})"
                                class="border-slate-300 text-brand-700 focus:ring-brand-500"
                            >
                            <span>{{ $option['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <textarea
                    wire:model.live.debounce.500ms="draftText"
                    rows="4"
                    class="mt-4 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    placeholder="اكتب إجابتك هنا..."
                ></textarea>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3">
            @if ($currentIndex === 0)
                <x-secondary-button wire:click="previous" disabled>→ السابق</x-secondary-button>
            @else
                <x-secondary-button wire:click="previous">→ السابق</x-secondary-button>
            @endif

            @if ($currentIndex < count($questions) - 1)
                <x-primary-button wire:click="next">التالي ←</x-primary-button>
            @else
                <x-primary-button wire:click="openReview">مراجعة الإجابات</x-primary-button>
            @endif
        </div>

        {{-- ===== المراجعة قبل التسليم ===== --}}
        @if ($showReview)
            <x-page-section title="مراجعة الإجابات" subtitle="اضغط على أي سؤال للرجوع إليه وتعديل إجابته.">
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8">
                    @foreach ($reviewItems as $item)
                        <button
                            type="button"
                            wire:click="goTo({{ $item['index'] }})"
                            wire:key="review-{{ $item['id'] }}"
                            @class([
                                'flex flex-col items-center gap-1 rounded-xl border px-2 py-3 text-xs font-semibold transition',
                                'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-400' => $item['answered'],
                                'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-400' => ! $item['answered'],
                            ])
                        >
                            <span>س{{ $item['index'] + 1 }}</span>
                            <span>{{ $item['answered'] ? '✓ مُجاب' : '⚠ بدون إجابة' }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm">
                    <div class="text-ink-muted">
                        مُجاب: <span class="font-bold text-emerald-600">{{ $answeredCount }}</span>
                        — بدون إجابة: <span class="font-bold text-amber-600">{{ $unansweredCount }}</span>
                        من {{ count($questions) }}
                    </div>
                    <div class="flex gap-2">
                        <x-secondary-button wire:click="closeReview">متابعة الحل</x-secondary-button>
                        <x-primary-button x-on:click="$dispatch('open-modal', 'confirm-submit')">تسليم الامتحان</x-primary-button>
                    </div>
                </div>
            </x-page-section>
        @endif

        {{-- ===== تأكيد التسليم ===== --}}
        <x-modal name="confirm-submit" maxWidth="md">
            <div class="space-y-4 p-6">
                <h3 class="text-lg font-bold text-ink">هل أنت متأكد من تسليم الامتحان؟</h3>

                @if ($unansweredCount > 0)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        ⚠ لديك <strong>{{ $unansweredCount }}</strong> سؤال بدون إجابة.
                    </div>
                @else
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        ✓ تمت الإجابة على جميع الأسئلة ({{ $answeredCount }} من {{ count($questions) }}).
                    </div>
                @endif

                <p class="text-sm text-ink-muted">بعد التسليم لن تستطيع تعديل إجاباتك.</p>

                <div class="flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-submit')">إلغاء</x-secondary-button>
                    <x-primary-button wire:click="submitExam" x-on:click="$dispatch('close-modal', 'confirm-submit')">تسليم نهائي</x-primary-button>
                </div>
            </div>
        </x-modal>
    @endif

    {{-- ===== معلومات الامتحان قبل البدء ===== --}}
    @if (is_null($resultScore) && is_null($attemptId) && $previewExamId && ($previewExam = $availableExams->firstWhere('id', $previewExamId)))
        <x-page-section title="{{ $previewExam->title }}" subtitle="معلومات الامتحان — تأكد قبل البدء.">
            <x-slot:actions>
                <x-secondary-button wire:click="cancelPreview">رجوع</x-secondary-button>
            </x-slot:actions>

            <div class="space-y-4">
                @if ($previewExam->description)
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ink-muted">{{ $previewExam->description }}</p>
                @endif

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="surface-stat">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">المادة</div>
                        <div class="mt-2 text-lg font-bold text-ink">{{ $previewExam->subject?->name }}</div>
                    </div>
                    <div class="surface-stat">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">المدة</div>
                        <div class="mt-2 text-lg font-bold text-ink">{{ $previewExam->duration_minutes }} دقيقة</div>
                    </div>
                    <div class="surface-stat">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">عدد الأسئلة</div>
                        <div class="mt-2 text-lg font-bold text-ink">{{ $previewExam->questions_count }}</div>
                    </div>
                    <div class="surface-stat">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">محاولاتك المتبقية</div>
                        <div class="mt-2 text-lg font-bold text-ink">
                            {{ max(0, $previewExam->max_attempts - ($attemptsByExam[$previewExam->id] ?? 0)) }}
                        </div>
                    </div>
                </div>

                @if ($previewExam->starts_at || $previewExam->ends_at)
                    <div class="text-xs text-ink-muted">
                        نافذة الامتحان:
                        {{ $previewExam->starts_at?->format('Y-m-d H:i') ?? '—' }}
                        →
                        {{ $previewExam->ends_at?->format('Y-m-d H:i') ?? '—' }}
                    </div>
                @endif

                <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                    يبدأ العدّاد فور الضغط على «ابدأ الامتحان» — لا تغلق الصفحة أثناء الحل، وإجاباتك تُحفظ تلقائيًا.
                </div>

                <x-primary-button wire:click="startExam({{ $previewExam->id }})">ابدأ الامتحان</x-primary-button>
            </div>
        </x-page-section>
    @endif

    {{-- ===== قائمة الامتحانات المتاحة ===== --}}
    @if (is_null($resultScore) && is_null($attemptId) && ! $previewExamId)
        <x-page-section title="الامتحانات المتاحة" subtitle="اضغط «دخول» لعرض معلومات الامتحان قبل البدء.">
            <div class="space-y-3">
                @forelse ($availableExams as $exam)
                    <div class="list-row">
                        <div class="text-sm">
                            <div class="font-semibold text-ink">{{ $exam->title }}</div>
                            <div class="text-ink-muted">
                                {{ $exam->subject?->name }}
                                — {{ $exam->duration_minutes }} دقيقة
                                — {{ $exam->questions_count }} سؤال
                                — {{ max(0, $exam->max_attempts - ($attemptsByExam[$exam->id] ?? 0)) }} محاولة متبقية
                            </div>
                        </div>
                        <x-primary-button wire:click="previewExam({{ $exam->id }})">دخول</x-primary-button>
                    </div>
                @empty
                    <div class="empty-state">
                        <p class="text-sm text-ink-muted">لا توجد امتحانات متاحة الآن.</p>
                    </div>
                @endforelse
            </div>
        </x-page-section>
    @endif
</div>
