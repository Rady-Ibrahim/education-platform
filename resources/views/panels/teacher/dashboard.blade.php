<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher($user);
        $subjects = $user->teachingSubjects()->with('grade')->orderBy('name')->get();
        $subjectLabel = $subjects->map(fn ($s) => $s->name.($s->grade ? ' — '.$s->grade->name : ''))->unique()->implode(' · ') ?: 'أضف مادتك من الملف الشخصي';
        $today = now()->locale('ar')->translatedFormat('l j F Y');
    @endphp

    <x-panel-page title="لوحة التشغيل" subtitle="إدارة اليوم الدراسي: المجموعات، الحضور، التحصيل، والدرجات.">
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
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" wire:navigate>إضافة طالب</a>
            <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="btn-accent" wire:navigate>تسجيل تحصيل</a>
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
                    <a href="{{ route('teacher.attendance') }}" class="hero-btn-ghost" wire:navigate>حضور اليوم</a>
                    <a href="{{ route('teacher.groups') }}" class="hero-btn-ghost" wire:navigate>المجموعات</a>
                    <a href="{{ route('teacher.exams.manual') }}" class="hero-btn-accent" wire:navigate>رصد درجة</a>
                </div>
            </div>
        </div>

        @if ($stats['attention_count'] > 0)
            <div class="attention-strip">
                <svg class="h-4 w-4 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span class="font-bold">يتطلب متابعة:</span>
                @if ($stats['owing_this_month'] > 0)
                    <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50" wire:navigate>{{ $stats['owing_this_month'] }} مستحقات الشهر</a>
                @endif
                @if ($stats['pending_payments'] > 0)
                    <a href="{{ route('teacher.payments', ['tab' => 'vodafone']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50" wire:navigate>{{ $stats['pending_payments'] }} إثبات فودافون</a>
                @endif
                @if ($stats['todays_absent'] > 0)
                    <a href="{{ route('teacher.attendance') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50" wire:navigate>{{ $stats['todays_absent'] }} غياب اليوم</a>
                @endif
                @if ($stats['pending_join_requests'] > 0)
                    <a href="{{ route('teacher.students.join') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50" wire:navigate>{{ $stats['pending_join_requests'] }} طلب انضمام</a>
                @endif
                @if ($stats['late_subscriptions'] > 0)
                    <a href="{{ route('teacher.payments') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200 hover:bg-amber-50" wire:navigate>{{ $stats['late_subscriptions'] }} اشتراك متأخر</a>
                @endif
            </div>
        @endif

        <section>
            <div class="section-head">
                <div>
                    <h2 class="section-title">مسار العمل اليومي</h2>
                    <p class="section-subtitle">نفس ترتيب إدارة السنتر — من المجموعة حتى التواصل مع ولي الأمر.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <x-flow-step step="1" title="المجموعات" description="مراجعة الأعضاء والحالة." :href="route('teacher.groups')" :badge="$stats['groups_count']" />
                <x-flow-step step="2" title="الحضور" description="تسجيل حاضر / غائب / متأخر." :href="route('teacher.attendance')" :tone="$stats['todays_absent'] > 0 ? 'warning' : 'default'" :badge="$stats['todays_absent']" />
                <x-flow-step step="3" title="التحصيل" description="دفتر الشهر والإيصالات." :href="route('teacher.payments', ['tab' => 'cash'])" tone="accent" :badge="$stats['owing_this_month']" />
                <x-flow-step step="4" title="الدرجات" description="رصد الامتحان الورقي." :href="route('teacher.exams.manual')" />
                <x-flow-step step="5" title="أولياء الأمور" description="رسائل الغياب والنتائج." :href="route('teacher.messages')" />
            </div>
        </section>

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

        <div class="grid gap-6 lg:grid-cols-5">
            <x-panel-card class="lg:col-span-3" title="المجموعات النشطة" subtitle="نظرة سريعة على شعبك ومواعيد الحصص.">
                <x-slot:actions>
                    <a href="{{ route('teacher.groups') }}" class="link-brand text-sm" wire:navigate>إدارة المجموعات</a>
                </x-slot:actions>

                @if (count($stats['groups']) === 0)
                    <div class="empty-state">
                        <p class="text-sm text-ink-muted">لا توجد مجموعات بعد. أنشئ مجموعة لكل مستوى أو موعد.</p>
                        <a href="{{ route('teacher.groups') }}" class="btn-brand mt-4" wire:navigate>إنشاء مجموعة</a>
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
                                    <a href="{{ route('teacher.attendance') }}" class="text-sm font-semibold text-brand-700" wire:navigate>حضور</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel-card>

            <x-panel-card class="lg:col-span-2" title="اختصارات" subtitle="المحتوى والامتحانات الإلكترونية.">
                <div class="space-y-2.5">
                    <x-quick-link href="{{ route('teacher.students') }}" title="قائمة الطلاب" description="بحث ومتابعة الاشتراك">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('teacher.lessons') }}" title="الدروس" description="محتوى ونشر">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('teacher.exams') }}" title="امتحان إلكتروني" description="بنك أسئلة ومحاولات">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </x-quick-link>
                </div>
            </x-panel-card>
        </div>
    </x-panel-page>
</x-app-layout>
