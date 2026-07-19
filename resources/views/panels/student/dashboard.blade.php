<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent($user);
        $teachers = $user->teachers;
        $needsJoin = $teachers->isEmpty();
        $needsPay = $stats['pending_subscriptions'] > 0;
    @endphp

    <x-panel-page title="لوحتي" subtitle="ثلاث حاجات بس: انضم لمدرسك، ادفع الاشتراك، اتعلم وحل الامتحانات.">
        <x-slot:actions>
            @if ($needsJoin)
                <a href="{{ route('teachers.index') }}" class="btn-brand" wire:navigate>تصفّح المدرسين</a>
            @elseif ($needsPay)
                <a href="{{ route('student.subscriptions') }}" class="btn-accent" wire:navigate>أكمل الدفع</a>
            @else
                <a href="{{ route('student.lessons') }}" class="btn-brand" wire:navigate>ابدأ التعلم</a>
            @endif
        </x-slot:actions>

        <div class="dashboard-hero">
            <div class="relative z-10 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold text-white/70">مرحباً</div>
                    <div class="mt-1 text-xl font-bold">{{ $user->name }}</div>
                    <p class="mt-2 text-sm text-white/75">
                        @if ($needsJoin)
                            ابدأ بطلب الانضمام لمدرسك.
                        @elseif ($needsPay)
                            عندك اشتراك بانتظار الدفع — ولي الأمر يقدر يدفع فودافون كاش.
                        @else
                            {{ $stats['active_subscriptions'] }} اشتراك نشط · {{ $stats['groups_count'] }} مجموعة
                        @endif
                    </p>
                </div>
                @if ($user->student_code)
                    <div class="rounded-2xl bg-white/10 px-4 py-3 text-center backdrop-blur">
                        <div class="text-[11px] font-semibold text-white/70">كود ولي الأمر</div>
                        <div class="mt-1 font-mono text-2xl font-bold tracking-[0.2em]" dir="ltr">{{ $user->student_code }}</div>
                    </div>
                @endif
            </div>
        </div>

        @if ($needsPay || $stats['unread_notifications'] > 0)
            <div class="attention-strip">
                <span class="font-bold">مطلوب منك:</span>
                @if ($needsPay)
                    <a href="{{ route('student.subscriptions') }}" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold underline-offset-2 hover:underline" wire:navigate>
                        دفع / إثبات اشتراك ({{ $stats['pending_subscriptions'] }})
                    </a>
                @endif
                @if ($stats['unread_notifications'] > 0)
                    <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold">
                        {{ $stats['unread_notifications'] }} إشعار جديد
                    </span>
                @endif
            </div>
        @endif

        <section>
            <div class="mb-3">
                <h2 class="section-title">مسارك</h2>
                <p class="section-subtitle">اتبع الخطوات بالترتيب — من غير لف.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <x-flow-step
                    step="1"
                    title="انضم لمدرس"
                    description="اطلب الانضمام أو انتظر إضافة المدرس لك."
                    :href="$needsJoin ? route('teachers.index') : route('student.dashboard')"
                    :tone="$needsJoin ? 'warning' : 'success'"
                />
                <x-flow-step
                    step="2"
                    title="الاشتراك والدفع"
                    description="ولي الأمر يدفع فودافون، أو كاش في السنتر."
                    :href="route('student.subscriptions')"
                    tone="accent"
                    :badge="$stats['pending_subscriptions']"
                />
                <x-flow-step
                    step="3"
                    title="دروس وامتحانات"
                    description="شاهد الدروس وحل الاختبارات المتاحة."
                    :href="route('student.lessons')"
                />
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="اشتراك نشط" :value="$stats['active_subscriptions']" :href="route('student.subscriptions')" tone="success">ن</x-stat-tile>
            <x-stat-tile label="بانتظار الدفع" :value="$stats['pending_subscriptions']" :href="route('student.subscriptions')" :tone="$needsPay ? 'warning' : 'default'">د</x-stat-tile>
            <x-stat-tile label="دروس مكتملة" :value="$stats['completed_lessons']" :href="route('student.lessons')">د</x-stat-tile>
            <x-stat-tile label="مجموعاتك" :value="$stats['groups_count']" hint="ضمن مدرسيك">م</x-stat-tile>
        </div>

        @if ($needsJoin)
            <x-panel-card title="انضم لمدرس" subtitle="ابعت طلب وانتظر الموافقة — أو سلّم كودك للمدرس ليضيفك.">
                <div class="max-w-xl">
                    <livewire:student.request-teacher-join />
                </div>
            </x-panel-card>
        @else
            <x-panel-card title="مدرسوك" :padding="false">
                <ul class="divide-y divide-slate-100">
                    @foreach ($teachers as $teacher)
                        <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-ink">{{ $teacher->name }}</div>
                                <div class="truncate text-xs text-ink-muted">{{ $teacher->headline ?: 'مدرس' }}</div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <a href="{{ route('student.exams') }}" class="text-sm font-semibold text-brand-700" wire:navigate>امتحانات</a>
                                @if ($teacher->slug)
                                    <a href="{{ route('teachers.show', $teacher->slug) }}" class="text-sm text-ink-muted hover:text-ink" wire:navigate>عرض</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-panel-card>
        @endif
    </x-panel-page>
</x-app-layout>
