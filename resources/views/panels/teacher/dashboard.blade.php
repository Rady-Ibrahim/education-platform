<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher($user);
        $subjects = $user->teachingSubjects()->with('grade')->orderBy('name')->get();
        $subjectLabel = $subjects->map(fn ($s) => $s->name.($s->grade ? ' — '.$s->grade->name : ''))->unique()->implode(' · ') ?: 'أضف مادتك من الملف الشخصي';
        $today = now()->locale('ar')->translatedFormat('l j F Y');
        $defaultTab = $stats['attention_count'] > 0 ? 'overview' : 'work';
    @endphp

    <x-panel-page title="لوحة التشغيل" subtitle="كل قسم لوحده — وافتح الحضور أو التحصيل في تاب متصفح منفصل بسهولة.">
        <x-slot:eyebrow>
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-900 ring-1 ring-brand-100">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                {{ $today }}
            </span>
        </x-slot:eyebrow>
        <x-slot:actions>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand">إضافة طالب</a>
            <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="btn-accent">تحصيل ↗</a>
        </x-slot:actions>

        <div class="dashboard-hero">
            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">المكتب اليومي</div>
                    <h2 class="mt-2 text-xl font-bold tracking-tight sm:text-2xl">{{ $subjectLabel }}</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="metric-chip">{{ $stats['groups_count'] }} مجموعة</span>
                        <span class="metric-chip">{{ $stats['students_count'] }} طالب</span>
                        <span class="metric-chip">{{ number_format($stats['confirmed_total'], 0) }} ج.م محصّل</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.attendance') }}" class="hero-btn-ghost">حضور اليوم ↗</a>
                    <a href="{{ route('teacher.groups') }}" class="hero-btn-ghost">المجموعات ↗</a>
                    <a href="{{ route('teacher.exams.manual') }}" class="hero-btn-accent">رصد درجة ↗</a>
                </div>
            </div>
        </div>

        <x-dashboard-tabs
            :tabs="[
                'overview' => 'نظرة عامة',
                'work' => 'تشغيل اليوم',
                'groups' => 'المجموعات',
                'content' => 'المحتوى',
                'more' => 'المزيد',
            ]"
            :default="$defaultTab"
        >
            <x-dashboard-panel name="overview">
                @if ($stats['attention_count'] > 0)
                    <div class="attention-strip">
                        <svg class="h-4 w-4 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span class="font-bold">يتطلب متابعة:</span>
                        @if ($stats['owing_this_month'] > 0)
                            <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50">{{ $stats['owing_this_month'] }} مستحقات الشهر ↗</a>
                        @endif
                        @if ($stats['pending_payments'] > 0)
                            <a href="{{ route('teacher.payments', ['tab' => 'vodafone']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50">{{ $stats['pending_payments'] }} إثبات فودافون ↗</a>
                        @endif
                        @if ($stats['todays_absent'] > 0)
                            <a href="{{ route('teacher.attendance') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50">{{ $stats['todays_absent'] }} غياب اليوم ↗</a>
                        @endif
                        @if ($stats['pending_join_requests'] > 0)
                            <a href="{{ route('teacher.students.join') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50">{{ $stats['pending_join_requests'] }} طلب انضمام ↗</a>
                        @endif
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-stat-tile label="الطلاب" :value="$stats['students_count']" :href="route('teacher.students')" hint="إجمالي المكتب">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="المجموعات" :value="$stats['groups_count']" :href="route('teacher.groups')" hint="حسب المستوى">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="المحصّل" :value="number_format($stats['confirmed_total'], 0).' ج.م'" :href="route('teacher.payments')" tone="success" hint="مدفوعات مؤكدة">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="معلّق" :value="$stats['pending_payments']" :href="route('teacher.payments', ['tab' => 'vodafone'])" :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'" hint="بانتظار المراجعة">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-stat-tile>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="work">
                <div class="dashboard-block">
                    <div class="mb-4">
                        <h3 class="dashboard-block-title">مسار العمل اليومي</h3>
                        <p class="dashboard-block-sub">اختصر يومك: مجموعات → حضور → تحصيل → درجات.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                        <x-flow-step step="1" title="المجموعات" description="مراجعة الأعضاء والحالة." :href="route('teacher.groups')" :badge="$stats['groups_count']" />
                        <x-flow-step step="2" title="الحضور" description="تسجيل حاضر / غائب / متأخر." :href="route('teacher.attendance')" :tone="$stats['todays_absent'] > 0 ? 'warning' : 'default'" :badge="$stats['todays_absent']" />
                        <x-flow-step step="3" title="التحصيل" description="دفتر الشهر والإيصالات." :href="route('teacher.payments', ['tab' => 'cash'])" tone="accent" :badge="$stats['owing_this_month']" />
                        <x-flow-step step="4" title="الدرجات" description="رصد الامتحان الورقي." :href="route('teacher.exams.manual')" />
                        <x-flow-step step="5" title="أولياء الأمور" description="رسائل الغياب والنتائج." :href="route('teacher.messages')" />
                    </div>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="groups">
                <div class="dashboard-block">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="dashboard-block-title">المجموعات النشطة</h3>
                            <p class="dashboard-block-sub">افتح الحضور مباشرة لكل مجموعة من تاب منفصل.</p>
                        </div>
                        <a href="{{ route('teacher.groups') }}" class="link-brand text-sm">إدارة المجموعات ↗</a>
                    </div>

                    @if (count($stats['groups']) === 0)
                        <div class="empty-state">
                            <p class="text-sm text-ink-muted">لا توجد مجموعات بعد.</p>
                            <a href="{{ route('teacher.groups') }}" class="btn-brand mt-4">إنشاء مجموعة ↗</a>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($stats['groups'] as $group)
                                <li class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-ink">{{ $group['name'] }}</div>
                                        <div class="mt-0.5 truncate text-xs text-ink-muted">
                                            {{ $group['grade'] ?? '—' }}
                                            @if ($group['schedule_note']) · {{ $group['schedule_note'] }} @endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-bold tabular-nums text-brand-900 ring-1 ring-brand-100">
                                            {{ $group['active_students'] }}
                                        </span>
                                        <a href="{{ route('teacher.attendance') }}" class="text-sm font-semibold text-brand-700">حضور ↗</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="content">
                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Lessons card --}}
                    <div class="dashboard-block">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="dashboard-block-title">الدروس والمحتوى</h3>
                                <p class="dashboard-block-sub">إضافة وتعديل وحدات ودروس المادة.</p>
                            </div>
                            <a href="{{ route('teacher.lessons') }}" wire:navigate class="link-brand shrink-0 text-sm">فتح ↗</a>
                        </div>
                        <div class="grid gap-2.5">
                            <a href="{{ route('teacher.lessons') }}" wire:navigate
                                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-soft">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-ink">إضافة درس جديد</span>
                                    <span class="text-xs text-ink-muted">نص · فيديو · لايف</span>
                                </span>
                            </a>
                            <a href="{{ route('teacher.lessons') }}" wire:navigate
                                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-soft">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5V6.5A2.5 2.5 0 016.5 4H20v13H6.5A2.5 2.5 0 004 19.5z"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-ink">إدارة الوحدات والدروس</span>
                                    <span class="text-xs text-ink-muted">تعديل، نشر، مرفقات</span>
                                </span>
                            </a>
                        </div>
                    </div>

                    {{-- Exams card --}}
                    <div class="dashboard-block">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="dashboard-block-title">الامتحانات الإلكترونية</h3>
                                <p class="dashboard-block-sub">بنك أسئلة · إنشاء امتحان · نتائج.</p>
                            </div>
                            <a href="{{ route('teacher.exams') }}" wire:navigate class="link-brand shrink-0 text-sm">فتح ↗</a>
                        </div>
                        <div class="grid gap-2.5">
                            <a href="{{ route('teacher.exams') }}" wire:navigate
                                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-soft">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-accent/20 text-accent-dark">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-ink">إنشاء امتحان جديد</span>
                                    <span class="text-xs text-ink-muted">حدد الأسئلة ونافذة الوقت</span>
                                </span>
                            </a>
                            <a href="{{ route('teacher.exams') }}" wire:navigate
                                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-soft">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7H9m3 4H9"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-ink">بنك الأسئلة</span>
                                    <span class="text-xs text-ink-muted">MCQ · صح/خطأ · مقال</span>
                                </span>
                            </a>
                            <a href="{{ route('teacher.exams.manual') }}" wire:navigate
                                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 transition hover:border-brand-200 hover:bg-white hover:shadow-soft">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-ink">رصد درجة ورقية</span>
                                    <span class="text-xs text-ink-muted">امتحانات ورقية يدوية</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="more">
                <div class="dashboard-block">
                    <div class="mb-4">
                        <h3 class="dashboard-block-title">اختصارات إضافية</h3>
                        <p class="dashboard-block-sub">روابط سريعة لباقي أقسام المكتب.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <x-quick-link href="{{ route('teacher.students') }}" title="قائمة الطلاب" description="بحث ومتابعة الاشتراك">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </x-quick-link>
                        <x-quick-link href="{{ route('teacher.messages') }}" title="رسائل أولياء الأمور" description="تواصل مباشر">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.116-3.72A7.8 7.8 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </x-quick-link>
                        <x-quick-link href="{{ route('teacher.platform') }}" title="اشتراك المنصة" description="رسوم الاشتراك">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </x-quick-link>
                    </div>
                </div>
            </x-dashboard-panel>
        </x-dashboard-tabs>
    </x-panel-page>
</x-app-layout>
