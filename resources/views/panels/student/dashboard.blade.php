<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent($user);
        $teachers = $user->teachers;
        $needsJoin = $teachers->isEmpty();
        $needsPay = $stats['pending_subscriptions'] > 0;
    @endphp

    <x-panel-page title="لوحتي" subtitle="أقسام منفصلة — وافتح الدروس أو الاشتراكات في تاب جديد وقت ما تحتاج.">
        <x-slot:actions>
            @if ($needsJoin)
                <a href="{{ route('teachers.index') }}" class="btn-brand" target="_blank" rel="noopener">تصفّح المدرسين ↗</a>
            @elseif ($needsPay)
                <a href="{{ route('student.subscriptions') }}" class="btn-accent" target="_blank" rel="noopener">إكمال الدفع ↗</a>
            @else
                <a href="{{ route('student.lessons') }}" class="btn-brand" target="_blank" rel="noopener">متابعة التعلم ↗</a>
            @endif
        </x-slot:actions>

        <div class="dashboard-hero">
            <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">حساب الطالب</div>
                    <h2 class="mt-2 text-xl font-bold tracking-tight sm:text-2xl">{{ $user->name }}</h2>
                    <p class="mt-2 max-w-lg text-sm leading-6 text-white/75">
                        @if ($needsJoin)
                            ابدأ بطلب الانضمام لمدرسك أو سلّم كودك له لإضافتك.
                        @elseif ($needsPay)
                            يوجد اشتراك بانتظار الدفع. يمكن لولي الأمر التحويل عبر فودافون كاش.
                        @else
                            {{ $stats['active_subscriptions'] }} اشتراك نشط · {{ $stats['groups_count'] }} مجموعة
                        @endif
                    </p>
                </div>
                @if ($user->student_code)
                    <div class="rounded-2xl bg-white/10 px-5 py-3.5 text-center ring-1 ring-white/15 backdrop-blur">
                        <div class="text-[11px] font-semibold tracking-wide text-white/65">كود ولي الأمر</div>
                        <div class="mt-1.5 font-mono text-2xl font-bold tracking-[0.22em]" dir="ltr">{{ $user->student_code }}</div>
                    </div>
                @endif
            </div>
        </div>

        <x-dashboard-tabs
            :tabs="[
                'overview' => 'نظرة عامة',
                'path' => 'المسار',
                'teachers' => 'المدرسون',
            ]"
            :default="$needsJoin || $needsPay ? 'path' : 'overview'"
        >
            <x-dashboard-panel name="overview">
                @if ($needsPay || $stats['unread_notifications'] > 0)
                    <div class="attention-strip">
                        <span class="font-bold">مطلوب منك:</span>
                        @if ($needsPay)
                            <a href="{{ route('student.subscriptions') }}" target="_blank" rel="noopener" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">
                                دفع الاشتراك ({{ $stats['pending_subscriptions'] }}) ↗
                            </a>
                        @endif
                        @if ($stats['unread_notifications'] > 0)
                            <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">
                                {{ $stats['unread_notifications'] }} إشعار جديد
                            </span>
                        @endif
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-stat-tile label="اشتراك نشط" :value="$stats['active_subscriptions']" :href="route('student.subscriptions')" tone="success">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="بانتظار الدفع" :value="$stats['pending_subscriptions']" :href="route('student.subscriptions')" :tone="$needsPay ? 'warning' : 'default'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="دروس مكتملة" :value="$stats['completed_lessons']" :href="route('student.lessons')">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="المجموعات" :value="$stats['groups_count']" hint="ضمن مدرسيك">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </x-stat-tile>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="path">
                <div class="dashboard-block">
                    <div class="mb-4">
                        <h3 class="dashboard-block-title">المسار الدراسي</h3>
                        <p class="dashboard-block-sub">ثلاث خطوات فقط — كل واحدة تفتح في تاب مستقل.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-flow-step step="1" title="الانضمام لمدرس" description="طلب انضمام أو إضافة يدوية من المدرس." :href="$needsJoin ? route('teachers.index') : route('student.dashboard')" :tone="$needsJoin ? 'warning' : 'success'" target="_blank" rel="noopener" />
                        <x-flow-step step="2" title="الاشتراك والدفع" description="كاش في السنتر أو فودافون من ولي الأمر." :href="route('student.subscriptions')" tone="accent" :badge="$stats['pending_subscriptions']" target="_blank" rel="noopener" />
                        <x-flow-step step="3" title="الدروس والامتحانات" description="متابعة المحتوى والاختبارات." :href="route('student.lessons')" target="_blank" rel="noopener" />
                    </div>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="teachers">
                <div class="dashboard-block">
                    @if ($needsJoin)
                        <div class="mb-4">
                            <h3 class="dashboard-block-title">الانضمام لمدرس</h3>
                            <p class="dashboard-block-sub">أرسل طلبًا وانتظر الموافقة، أو سلّم كودك للمدرس.</p>
                        </div>
                        <div class="max-w-xl">
                            <livewire:student.request-teacher-join />
                        </div>
                    @else
                        <div class="mb-4">
                            <h3 class="dashboard-block-title">مدرّسوك</h3>
                            <p class="dashboard-block-sub">افتح الامتحانات في تاب جديد أثناء المتابعة.</p>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($teachers as $teacher)
                                <li class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-ink">{{ $teacher->name }}</div>
                                        <div class="truncate text-xs text-ink-muted">{{ $teacher->headline ?: 'مدرس' }}</div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <a href="{{ route('student.exams') }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-700">امتحانات ↗</a>
                                        @if ($teacher->slug)
                                            <a href="{{ route('teachers.show', $teacher->slug) }}" target="_blank" rel="noopener" class="text-sm text-ink-muted hover:text-ink">الملف ↗</a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </x-dashboard-panel>
        </x-dashboard-tabs>
    </x-panel-page>
</x-app-layout>
