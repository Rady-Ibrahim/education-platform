<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12">

    {{-- Page header --}}
    <div class="max-w-2xl">
        <div class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.1em] text-brand-700 ring-1 ring-brand-100">
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            {{ config('app.name', 'سنتر') }}
        </div>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-ink sm:text-4xl">المدرسون</h1>
        <p class="mt-2.5 text-base leading-relaxed text-ink-muted">اختر مدرسك، شوف خطط الاشتراك، وانضم قبل أو بعد التسجيل. الدفع كاش في السنتر أو فودافون كاش.</p>
    </div>

    {{-- Filter bar --}}
    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card">
        <div class="border-b border-slate-100 px-5 py-3.5">
            <p class="text-xs font-semibold text-ink-muted">تصفية النتائج</p>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-3">
            <div>
                <x-input-label for="search" value="بحث" />
                <div class="relative mt-1">
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                        <svg class="h-4 w-4 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <x-text-input wire:model.live.debounce.300ms="search" id="search" class="block w-full pe-10" placeholder="اسم المدرس أو التخصص" />
                </div>
            </div>
            <div>
                <x-input-label for="gradeId" value="الصف" />
                <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full">
                    <option value="">كل الصفوف</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="subjectId" value="المادة" />
                <select wire:model.live="subjectId" id="subjectId" class="mt-1 block w-full">
                    <option value="">الكل</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Results count --}}
    <div class="mt-6 flex items-center justify-between gap-3">
        <p class="text-sm text-ink-muted">
            <span class="font-semibold text-ink">{{ $teachers->total() }}</span> مدرس متاح
        </p>
        @if ($search || $gradeId || $subjectId)
            <button wire:click="$set('search', ''); $set('gradeId', ''); $set('subjectId', '')" class="text-xs font-semibold text-brand-700 transition hover:text-brand-900">
                مسح التصفية ×
            </button>
        @endif
    </div>

    {{-- Teacher grid --}}
    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($teachers as $teacher)
            @php
                $cover = $teacher->coverUrl();
                $avatar = $teacher->avatarUrl();
                $startingPlan = $teacher->subscriptionPlans->first();
                $subjectLabel = $teacher->teachingSubjects->take(2)->map(function ($subject) {
                    return $subject->name.($subject->grade ? ' · '.$subject->grade->name : '');
                })->join(' · ');
            @endphp
            <a
                href="{{ route('teachers.show', $teacher->slug) }}"
                wire:navigate
                class="teacher-card group flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-card-hover"
            >
                {{-- Cover --}}
                <div class="relative aspect-[16/9] overflow-hidden bg-brand-100">
                    @if ($cover)
                        <img
                            src="{{ $cover }}"
                            alt="{{ $teacher->name }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.05]"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-300 via-brand-200 to-accent/30">
                            <span class="brand-mark h-16 w-16 text-2xl shadow-soft">{{ mb_substr($teacher->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                    {{-- Price badge on cover --}}
                    @if ($startingPlan)
                        <div class="absolute end-3 top-3 rounded-xl bg-black/50 px-2.5 py-1 text-xs font-bold text-white backdrop-blur-sm ring-1 ring-white/10">
                            من {{ number_format((float) $startingPlan->price, 0) }} ج.م
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex flex-1 flex-col p-4 sm:p-5">
                    {{-- Avatar + name --}}
                    <div class="flex items-start gap-3">
                        @if ($avatar)
                            <img src="{{ $avatar }}" alt="" class="mt-0.5 h-10 w-10 shrink-0 rounded-xl object-cover ring-2 ring-white shadow-soft">
                        @else
                            <span class="brand-mark mt-0.5 h-10 w-10 shrink-0 shadow-soft">{{ mb_substr($teacher->name, 0, 1) }}</span>
                        @endif
                        <div class="min-w-0">
                            <h2 class="line-clamp-1 text-base font-bold leading-snug text-ink transition duration-200 group-hover:text-brand-800">
                                {{ $teacher->name }}
                            </h2>
                            @if ($teacher->headline)
                                <p class="mt-0.5 line-clamp-1 text-sm text-brand-800">{{ $teacher->headline }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($subjectLabel !== '')
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach (explode(' · ', $subjectLabel) as $tag)
                                <span class="rounded-lg bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-900 ring-1 ring-brand-100">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-muted">
                        {{ $teacher->bio ?: 'مدرس في السنتر.' }}
                    </p>

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
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
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 transition duration-200 group-hover:gap-2 group-hover:text-brand-900">
                            عرض الصفحة
                            <svg class="h-3.5 w-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <div class="empty-state py-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <p class="text-base font-semibold text-ink">لا يوجد مدرسون بهذه المعايير</p>
                    <p class="mt-1 text-sm text-ink-muted">اطلب من المدرس إكمال بروفايله وإظهار صفحته للعامة.</p>
                    @if ($search || $gradeId || $subjectId)
                        <button wire:click="$set('search', ''); $set('gradeId', ''); $set('subjectId', '')" class="btn-ghost mt-5">
                            مسح التصفية
                        </button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $teachers->links() }}
    </div>
</div>
