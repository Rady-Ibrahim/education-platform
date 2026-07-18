<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher(auth()->user());
        $subjects = app(\App\Modules\Academic\Services\AcademicStructureService::class)
            ->subjectsForTeacher(auth()->user());
        $subjectLabel = $subjects->map(fn ($s) => $s->name)->unique()->implode(' · ') ?: null;
    @endphp

    <x-panel-page
        title="لوحة المدرس"
        :subtitle="$subjectLabel ? 'تدريس: '.$subjectLabel : 'نظرة سريعة على مجموعتك والمهام العاجلة.'"
    >
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}#add-student" class="btn-brand" wire:navigate>إضافة طالب</a>
            <a href="{{ route('teacher.lessons') }}" class="btn-accent" wire:navigate>إضافة درس</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile
                label="الطلاب"
                :value="$stats['students_count']"
                hint="في مجموعتك"
                :href="route('teacher.students')"
                tone="default"
            >ط</x-stat-tile>

            <x-stat-tile
                label="التحصيل"
                :value="number_format($stats['confirmed_total'], 0).' ج.م'"
                hint="مدفوعات مؤكدة"
                :href="route('teacher.payments')"
                tone="success"
            >ج</x-stat-tile>

            <x-stat-tile
                label="بانتظار المراجعة"
                :value="$stats['pending_payments']"
                hint="فودافون كاش / إثباتات"
                :href="route('teacher.payments')"
                :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'"
            >م</x-stat-tile>

            <x-stat-tile
                label="طلبات انضمام"
                :value="$stats['pending_join_requests']"
                hint="طلاب جدد ينتظرون"
                :href="route('teacher.students').'#join-requests'"
                :tone="$stats['pending_join_requests'] > 0 ? 'warning' : 'default'"
            >ط</x-stat-tile>
        </div>

        @if ($stats['pending_payments'] > 0 || $stats['pending_join_requests'] > 0 || $stats['late_subscriptions'] > 0)
            <x-panel-card title="يحتاج انتباهك" subtitle="المهام العاجلة فقط — التفاصيل من صفحاتها.">
                <ul class="divide-y divide-slate-100">
                    @if ($stats['pending_join_requests'] > 0)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <div class="text-sm font-bold text-ink">{{ $stats['pending_join_requests'] }} طلب انضمام</div>
                                <div class="text-xs text-ink-muted">راجع ووافق على الطلاب الجدد</div>
                            </div>
                            <a href="{{ route('teacher.students') }}#join-requests" class="btn-brand !px-3 !py-2 text-xs" wire:navigate>مراجعة</a>
                        </li>
                    @endif
                    @if ($stats['pending_payments'] > 0)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <div class="text-sm font-bold text-ink">{{ $stats['pending_payments'] }} دفعة معلّقة</div>
                                <div class="text-xs text-ink-muted">أكد أو ارفض إثباتات الدفع</div>
                            </div>
                            <a href="{{ route('teacher.payments') }}" class="btn-brand !px-3 !py-2 text-xs" wire:navigate>المدفوعات</a>
                        </li>
                    @endif
                    @if ($stats['late_subscriptions'] > 0)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <div class="text-sm font-bold text-ink">{{ $stats['late_subscriptions'] }} اشتراك متأخر</div>
                                <div class="text-xs text-ink-muted">بانتظار الدفع أكثر من 3 أيام</div>
                            </div>
                            <a href="{{ route('teacher.students') }}" class="link-brand text-sm" wire:navigate>الطلاب</a>
                        </li>
                    @endif
                </ul>
            </x-panel-card>
        @endif

        <div>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h2 class="section-title">اختصارات</h2>
                    <p class="section-subtitle">انتقل مباشرة للمهمة — بدون حشو في اللوحة.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-quick-link href="{{ route('teacher.students') }}" title="الطلاب" description="بحث، اشتراكات، إضافة طالب">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </x-quick-link>
                <x-quick-link href="{{ route('teacher.lessons') }}" title="الدروس" description="نص، فيديو، لايف، مرفقات">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </x-quick-link>
                <x-quick-link href="{{ route('teacher.exams') }}" title="الامتحانات" description="بنك أسئلة وتصحيح">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </x-quick-link>
                <x-quick-link href="{{ route('teacher.payments') }}" title="المدفوعات" description="خطط، كاش، فودافون">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </x-quick-link>
            </div>
        </div>
    </x-panel-page>
</x-app-layout>
