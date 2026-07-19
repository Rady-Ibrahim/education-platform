<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
    @endphp

    <x-panel-page title="متابعة الأبناء" subtitle="كل قسم لوحده — وافتح النتائج أو المدفوعات في تاب متصفح منفصل.">
        <x-slot:actions>
            <a href="{{ route('parent.link') }}" class="btn-brand">ربط ابن</a>
            <a href="{{ route('parent.messages') }}" class="btn-accent" target="_blank" rel="noopener">
                الرسائل ↗
                @if ($stats['unread_messages'] > 0)
                    <span class="ms-1 rounded-md bg-ink/10 px-1.5 py-0.5 text-[11px]">{{ $stats['unread_messages'] }}</span>
                @endif
            </a>
        </x-slot:actions>

        <div class="dashboard-hero">
            <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55">ولي الأمر</div>
                    <h2 class="mt-2 text-xl font-bold tracking-tight sm:text-2xl">
                        {{ $stats['children_count'] }} {{ $stats['children_count'] === 1 ? 'ابن مرتبط' : 'أبناء مرتبطون' }}
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/75">
                        تابع النتائج، سجّل دفع فودافون كاش، واستقبل رسائل المدرس.
                    </p>
                </div>
                <a href="{{ route('parent.exams') }}" class="hero-btn-ghost" target="_blank" rel="noopener">كل النتائج ↗</a>
            </div>
        </div>

        <x-dashboard-tabs
            :tabs="[
                'overview' => 'نظرة عامة',
                'actions' => 'المهام',
                'children' => 'الأبناء',
            ]"
            :default="$stats['children_count'] === 0 ? 'actions' : 'children'"
        >
            <x-dashboard-panel name="overview">
                @if ($stats['attention_count'] > 0)
                    <div class="attention-strip">
                        <span class="font-bold">يتطلب متابعتك:</span>
                        @if ($stats['unread_messages'] > 0)
                            <a href="{{ route('parent.messages') }}" target="_blank" rel="noopener" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">
                                {{ $stats['unread_messages'] }} رسالة من مدرس ↗
                            </a>
                        @endif
                        @foreach ($stats['children'] as $child)
                            @if ($child['pending_subscriptions'] > 0 || $child['pending_payments'] > 0)
                                <a href="{{ route('parent.children.payments', $child['id']) }}" target="_blank" rel="noopener" class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">
                                    دفع — {{ $child['name'] }} ↗
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-3">
                    <x-stat-tile label="الأبناء" :value="$stats['children_count']" tone="success">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="رسائل غير مقروءة" :value="$stats['unread_messages']" :href="route('parent.messages')" :tone="$stats['unread_messages'] > 0 ? 'warning' : 'default'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.116-3.72A7.8 7.8 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </x-stat-tile>
                    <x-stat-tile label="الإشعارات" :value="$stats['unread_notifications']" :tone="$stats['unread_notifications'] > 0 ? 'warning' : 'default'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </x-stat-tile>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="actions">
                <div class="dashboard-block">
                    <div class="mb-4">
                        <h3 class="dashboard-block-title">المهام الأساسية</h3>
                        <p class="dashboard-block-sub">كل مهمة تفتح في تاب جديد بدون ما تضيع الصفحة الحالية.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-flow-step step="1" title="ربط الابن" description="بكود الطالب من المدرس أو حساب الطالب." :href="route('parent.link')" :tone="$stats['children_count'] === 0 ? 'warning' : 'success'" target="_blank" rel="noopener" />
                        <x-flow-step step="2" title="دفع المصاريف" description="فودافون كاش بإثبات — المدرس يراجع." :href="$stats['children_count'] ? route('parent.children.payments', $stats['children'][0]['id']) : route('parent.link')" tone="accent" target="_blank" rel="noopener" />
                        <x-flow-step step="3" title="متابعة الدرجات" description="نتائج إلكترونية وورقية + رسائل." :href="route('parent.exams')" :badge="$stats['unread_messages']" target="_blank" rel="noopener" />
                    </div>
                </div>
            </x-dashboard-panel>

            <x-dashboard-panel name="children">
                <div class="dashboard-block">
                    <div class="mb-4">
                        <h3 class="dashboard-block-title">الأبناء</h3>
                        <p class="dashboard-block-sub">النتائج والمدفوعات تفتح كل واحدة في تاب مستقل.</p>
                    </div>
                    <div class="space-y-3">
                        @forelse ($stats['children'] as $child)
                            <div class="child-card">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-base font-bold text-ink">{{ $child['name'] }}</div>
                                        <div class="mt-1 font-mono text-xs text-ink-muted" dir="ltr">{{ $child['student_code'] ?? '—' }}</div>
                                        @if (count($child['groups']) > 0)
                                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                                @foreach ($child['groups'] as $groupLabel)
                                                    <span class="rounded-lg bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-900 ring-1 ring-brand-100">
                                                        {{ $groupLabel }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('parent.children.exams', $child['id']) }}" target="_blank" rel="noopener" class="btn-brand !px-3 !py-2 text-xs">النتائج ↗</a>
                                        <a href="{{ route('parent.children.payments', $child['id']) }}" target="_blank" rel="noopener" class="btn-accent !px-3 !py-2 text-xs">المدفوعات ↗</a>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-4">
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">اشتراك نشط</div>
                                        <div class="mt-1 font-bold tabular-nums text-ink">{{ $child['active_subscriptions'] }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">بانتظار الدفع</div>
                                        <div @class(['mt-1 font-bold tabular-nums', 'text-amber-800' => $child['pending_subscriptions'] > 0, 'text-ink' => $child['pending_subscriptions'] === 0])>
                                            {{ $child['pending_subscriptions'] }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">دروس مكتملة</div>
                                        <div class="mt-1 font-bold tabular-nums text-ink">{{ $child['completed_lessons'] }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">متوسط الدرجات</div>
                                        <div class="mt-1 font-bold tabular-nums text-ink">{{ $child['average_exam_score'] !== null ? $child['average_exam_score'].'%' : '—' }}</div>
                                    </div>
                                </div>

                                @if (count($child['recent_absences']) > 0)
                                    <div class="mt-4 rounded-xl bg-rose-50 px-3.5 py-3 ring-1 ring-rose-100">
                                        <div class="text-xs font-bold text-rose-900">سجل الحضور (آخر 7 أيام)</div>
                                        <ul class="mt-1.5 space-y-1 text-xs text-rose-900/90">
                                            @foreach ($child['recent_absences'] as $absence)
                                                <li class="tabular-nums">{{ $absence['date'] }} — {{ $absence['group'] }} ({{ $absence['status'] }})</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state">
                                <p class="text-sm text-ink-muted">لا يوجد أبناء مرتبطون بعد.</p>
                                <a href="{{ route('parent.link') }}" class="btn-brand mt-4" target="_blank" rel="noopener">ربط ابن بكود الطالب ↗</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </x-dashboard-panel>
        </x-dashboard-tabs>
    </x-panel-page>
</x-app-layout>
