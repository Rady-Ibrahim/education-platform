<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold text-brand-700">{{ config('app.name', 'سنتر') }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink sm:text-4xl">المدرسون</h1>
        <p class="mt-2 text-ink-muted">اختر مدرسك، شوف خطط الاشتراك، وانضم قبل أو بعد التسجيل. الدفع كاش في السنتر أو فودافون كاش.</p>
    </div>

    <div class="mt-8 grid gap-4 rounded-2xl border border-brand-100/80 bg-white/80 p-4 shadow-soft sm:grid-cols-3 sm:p-5">
        <div>
            <x-input-label for="search" value="بحث" />
            <x-text-input wire:model.live.debounce.300ms="search" id="search" class="mt-1 block w-full" placeholder="اسم المدرس أو التخصص" />
        </div>
        <div>
            <x-input-label for="gradeId" value="الصف" />
            <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">كل الصفوف</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="subjectId" value="المادة" />
            <select wire:model.live="subjectId" id="subjectId" class="mt-1 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">الكل</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($teachers as $teacher)
            @php
                $cover = $teacher->coverUrl();
                $startingPlan = $teacher->subscriptionPlans->first();
                $subjectLabel = $teacher->teachingSubjects->take(2)->map(function ($subject) {
                    return $subject->name.($subject->grade ? ' · '.$subject->grade->name : '');
                })->join(' · ');
            @endphp
            <a
                href="{{ route('teachers.show', $teacher->slug) }}"
                wire:navigate
                class="teacher-card group flex flex-col overflow-hidden rounded-2xl border border-brand-100/90 bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-panel"
            >
                <div class="relative aspect-[16/10] overflow-hidden bg-brand-100">
                    @if ($cover)
                        <img
                            src="{{ $cover }}"
                            alt="{{ $teacher->name }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-200 via-brand-100 to-accent/40">
                            <span class="brand-mark h-16 w-16 text-2xl">{{ mb_substr($teacher->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent opacity-80"></div>
                </div>

                <div class="flex flex-1 flex-col p-4 sm:p-5">
                    <h2 class="line-clamp-2 text-lg font-bold leading-snug text-ink transition group-hover:text-brand-800">
                        {{ $teacher->name }}
                    </h2>

                    @if ($teacher->headline)
                        <p class="mt-1 line-clamp-2 text-sm text-brand-800">{{ $teacher->headline }}</p>
                    @endif

                    @if ($subjectLabel !== '')
                        <p class="mt-3 text-xs font-medium text-ink-muted">{{ $subjectLabel }}</p>
                    @endif

                    <p class="mt-2 line-clamp-2 text-sm text-ink-muted">
                        {{ $teacher->bio ?: 'مدرس في السنتر.' }}
                    </p>

                    <div class="mt-auto flex items-end justify-between gap-3 border-t border-brand-50 pt-4">
                        <div>
                            @if ($startingPlan)
                                <div class="text-[11px] text-ink-muted">يبدأ من</div>
                                <div class="text-base font-bold text-ink">
                                    {{ number_format((float) $startingPlan->price, 0) }}
                                    <span class="text-sm font-semibold text-ink-muted">ج.م</span>
                                </div>
                            @else
                                <div class="text-sm text-ink-muted">خطط قريبًا</div>
                            @endif
                        </div>
                        <span class="text-sm font-semibold text-brand-700 transition group-hover:text-brand-900">
                            عرض الصفحة
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-dashed border-brand-200 bg-white/70 p-12 text-center text-ink-muted">
                لا يوجد مدرسون ظاهرون حاليًا. اطلب من المدرس إكمال بروفايله وإظهار صفحته للعامة.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $teachers->links() }}
    </div>
</div>
