<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher($user);
        $subjects = $user->teachingSubjects()->with('grade')->orderBy('name')->get();
        $subjectLabel = $subjects->map(fn ($s) => $s->name.($s->grade ? ' — '.$s->grade->name : ''))->unique()->implode(' · ') ?: 'حدّد مادتك من البروفايل';
        $today = now()->locale('ar')->translatedFormat('l j F');
    @endphp

    <x-panel-page title="مكتب اليوم" :subtitle="'إدارة مجموعتك زي السنتر: مجموعات → تحصيل → درجات → تواصل مع ولي الأمر.'">
        <x-slot:eyebrow>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-900">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                {{ $today }}
            </span>
        </x-slot:eyebrow>
        <x-slot:actions>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" wire:navigate>إضافة طالب</a>
            <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="btn-accent" wire:navigate>تسجيل تحصيل</a>
        </x-slot:actions>

        <div class="dashboard-hero">
            <div class="relative z-10 flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-xl">
                    <div class="text-xs font-semibold uppercase tracking-wide text-white/70">تدريس</div>
                    <div class="mt-1 text-lg font-bold sm:text-xl">{{ $subjectLabel }}</div>
                    <p class="mt-2 text-sm leading-relaxed text-white/75">
                        {{ $stats['groups_count'] }} مجموعة · {{ $stats['students_count'] }} طالب في مكتبك
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('teacher.groups') }}" class="rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/25" wire:navigate>
                        المجموعات
                    </a>
                    <a href="{{ route('teacher.attendance') }}" class="rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/25" wire:navigate>
                        حضور اليوم
                    </a>
                    <a href="{{ route('teacher.exams.manual') }}" class="rounded-xl bg-accent px-4 py-2.5 text-sm font-bold text-ink transition hover:bg-accent-dark hover:text-white" wire:navigate>
                        رصد درجة
                    </a>
                </div>
            </div>
        </div>

        @if ($stats['attention_count'] > 0)
            <div class="attention-strip">
                <span class="font-bold">يحتاج انتباهك:</span>
                @if ($stats['pending_payments'] > 0)
                    <a href="{{ route('teacher.payments', ['tab' => 'vodafone']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-amber-950 underline-offset-2 hover:underline" wire:navigate>
                        {{ $stats['pending_payments'] }} إثبات دفع
                    </a>
                @endif
                @if ($stats['pending_join_requests'] > 0)
                    <a href="{{ route('teacher.students.join') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-amber-950 underline-offset-2 hover:underline" wire:navigate>
                        {{ $stats['pending_join_requests'] }} طلب انضمام
                    </a>
                @endif
                @if ($stats['late_subscriptions'] > 0)
                    <a href="{{ route('teacher.payments') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-amber-950 underline-offset-2 hover:underline" wire:navigate>
                        {{ $stats['late_subscriptions'] }} اشتراك متأخر
                    </a>
                @endif
                @if ($stats['todays_absent'] > 0)
                    <a href="{{ route('teacher.attendance') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-amber-950 underline-offset-2 hover:underline" wire:navigate>
                        {{ $stats['todays_absent'] }} غياب اليوم
                    </a>
                @endif
                @if ($stats['owing_this_month'] > 0)
                    <a href="{{ route('teacher.payments', ['tab' => 'cash']) }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-amber-950 underline-offset-2 hover:underline" wire:navigate>
                        {{ $stats['owing_this_month'] }} عليه فلوس الشهر
                    </a>
                @endif
            </div>
        @endif

        <section>
            <div class="mb-3">
                <h2 class="section-title">الفلو اليومي</h2>
                <p class="section-subtitle">نفس ترتيب السنتر اليدوي — خطوة ورا خطوة.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <x-flow-step
                    step="1"
                    title="المجموعات"
                    description="راجع أعضاء مجموعة الحصة."
                    :href="route('teacher.groups')"
                    :badge="$stats['groups_count']"
                />
                <x-flow-step
                    step="2"
                    title="الحضور"
                    description="سجّل حاضر / غائب زي كشف الورق."
                    :href="route('teacher.attendance')"
                    :tone="$stats['todays_absent'] > 0 ? 'warning' : 'default'"
                    :badge="$stats['todays_absent']"
                />
                <x-flow-step
                    step="3"
                    title="التحصيل"
                    description="دفتر الشهر: كامل / جزئي / متبقي + إيصال."
                    :href="route('teacher.payments', ['tab' => 'cash'])"
                    tone="accent"
                    :badge="$stats['owing_this_month']"
                />
                <x-flow-step
                    step="4"
                    title="الدرجات"
                    description="امتحان ورقي: رصد الدرجة."
                    :href="route('teacher.exams.manual')"
                />
                <x-flow-step
                    step="5"
                    title="أولياء الأمور"
                    description="بلّغ بالغياب أو الدرجة أو المصاريف."
                    :href="route('teacher.messages')"
                    :tone="$stats['pending_join_requests'] > 0 ? 'warning' : 'default'"
                />
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="الطلاب" :value="$stats['students_count']" :href="route('teacher.students')" hint="كل مكتبك">ط</x-stat-tile>
            <x-stat-tile label="المجموعات" :value="$stats['groups_count']" :href="route('teacher.groups')" hint="حسب المستوى">م</x-stat-tile>
            <x-stat-tile label="محصّل" :value="number_format($stats['confirmed_total'], 0).' ج.م'" :href="route('teacher.payments')" tone="success" hint="مؤكد">ج</x-stat-tile>
            <x-stat-tile
                label="معلّق"
                :value="$stats['pending_payments']"
                :href="route('teacher.payments', ['tab' => 'vodafone'])"
                :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'"
                hint="بانتظار المراجعة"
            >د</x-stat-tile>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <x-panel-card class="lg:col-span-3" title="مجموعاتك" subtitle="اضغط مجموعة لإدارة أعضائها من صفحة المجموعات.">
                <x-slot:actions>
                    <a href="{{ route('teacher.groups') }}" class="link-brand" wire:navigate>إدارة الكل</a>
                </x-slot:actions>

                @if (count($stats['groups']) === 0)
                    <div class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center">
                        <p class="text-sm text-ink-muted">لسه ما عندكش مجموعات — ابدأ بإنشاء مجموعة لكل مستوى.</p>
                        <a href="{{ route('teacher.groups') }}" class="btn-brand mt-4" wire:navigate>إنشاء أول مجموعة</a>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($stats['groups'] as $group)
                            <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-ink">{{ $group['name'] }}</div>
                                    <div class="mt-0.5 truncate text-xs text-ink-muted">
                                        {{ $group['grade'] ?? '—' }}
                                        @if ($group['schedule_note']) · {{ $group['schedule_note'] }} @endif
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-900">
                                        {{ $group['active_students'] }} مستمر
                                    </span>
                                    <a href="{{ route('teacher.groups') }}" class="text-sm font-semibold text-brand-700" wire:navigate>فتح</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel-card>

            <x-panel-card class="lg:col-span-2" title="اختصارات" subtitle="المحتوى والمنصة.">
                <div class="space-y-3">
                    <x-quick-link href="{{ route('teacher.students') }}" title="كل الطلاب" description="بحث ومتابعة الاشتراك">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('teacher.lessons') }}" title="الدروس" description="محتوى إلكتروني ونشر">
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
