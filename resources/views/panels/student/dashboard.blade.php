<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent($user);
        $teachers = $user->teachers;
    @endphp

    <x-panel-page title="لوحتي" subtitle="ملخص سريع لتعلّمك واشتراكاتك.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="btn-brand" wire:navigate>ابدأ التعلم</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="اشتراكات نشطة" :value="$stats['active_subscriptions']" :href="route('student.subscriptions')" tone="success">ن</x-stat-tile>
            <x-stat-tile
                label="بانتظار الدفع"
                :value="$stats['pending_subscriptions']"
                :hint="$stats['pending_subscriptions'] > 0 ? 'أكمل الدفع للمتابعة' : null"
                :href="route('student.subscriptions')"
                :tone="$stats['pending_subscriptions'] > 0 ? 'warning' : 'default'"
            >د</x-stat-tile>
            <x-stat-tile label="دروس مكتملة" :value="$stats['completed_lessons']" :href="route('student.lessons')">د</x-stat-tile>
            <x-stat-tile
                label="إشعارات"
                :value="$stats['unread_notifications']"
                :tone="$stats['unread_notifications'] > 0 ? 'warning' : 'default'"
            >إ</x-stat-tile>
        </div>

        @if ($user->student_code)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-brand-200/80 bg-white px-5 py-4 shadow-soft">
                <div>
                    <div class="text-sm font-bold text-ink">كود ولي الأمر</div>
                    <p class="mt-0.5 text-xs text-ink-muted">شاركه مع ولي أمرك للربط والدفع.</p>
                </div>
                <div class="rounded-xl bg-brand-50 px-4 py-2 font-mono text-2xl font-bold tracking-widest text-brand-950" dir="ltr">
                    {{ $user->student_code }}
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            <div>
                <div class="mb-3">
                    <h2 class="section-title">اختصارات</h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-quick-link href="{{ route('student.lessons') }}" title="الدروس" description="شاهد وتابع تقدّمك">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('student.exams') }}" title="الامتحانات" description="الاختبارات المتاحة">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('student.subscriptions') }}" title="الاشتراكات" description="الخطط وإثبات الدفع">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </x-quick-link>
                    <x-quick-link href="{{ route('student.certificates') }}" title="الشهادات" description="شهادات الاجتياز">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </x-quick-link>
                </div>
            </div>

            <x-panel-card title="مدرسوك" :padding="false">
                <ul class="divide-y divide-slate-100">
                    @forelse ($teachers as $teacher)
                        <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 text-sm font-bold text-brand-800">
                                    {{ mb_substr($teacher->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-ink">{{ $teacher->name }}</div>
                                    <div class="truncate text-xs text-ink-muted">{{ $teacher->headline ?: 'مدرس' }}</div>
                                </div>
                            </div>
                            @if ($teacher->slug)
                                <a href="{{ route('teachers.show', $teacher->slug) }}" class="text-sm font-semibold text-brand-700" wire:navigate>عرض</a>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-ink-muted">
                            لسه منضمّتش لأي مدرس.
                            <a href="{{ route('teachers.index') }}" class="mt-2 block link-brand" wire:navigate>تصفّح المدرسين</a>
                        </li>
                    @endforelse
                </ul>
            </x-panel-card>
        </div>

        @if ($teachers->isEmpty())
            <x-panel-card title="انضم لمدرس" subtitle="ابعت طلب وانتظر الموافقة.">
                <div class="max-w-xl">
                    <livewire:student.request-teacher-join />
                </div>
            </x-panel-card>
        @endif

        <details class="surface-panel group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="section-title">أولياء الأمور</h2>
                    <p class="section-subtitle">طلبات الربط وإدارة الأولياء المرتبطين</p>
                </div>
                <span class="text-xs font-semibold text-brand-700 group-open:hidden">إظهار</span>
                <span class="hidden text-xs font-semibold text-brand-700 group-open:inline">إخفاء</span>
            </summary>
            <div class="p-5 sm:p-6">
                <livewire:student.parent-link-requests />
            </div>
        </details>
    </x-panel-page>
</x-app-layout>
