<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
    @endphp

    <x-panel-page title="لوحة ولي الأمر" subtitle="تابع أبناءك والاشتراكات والمدفوعات.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-stat-tile label="الأبناء المرتبطون" :value="$stats['children_count']" tone="success">أ</x-stat-tile>
            <x-stat-tile
                label="إشعارات"
                :value="$stats['unread_notifications']"
                :tone="$stats['unread_notifications'] > 0 ? 'warning' : 'default'"
            >إ</x-stat-tile>
        </div>

        <x-panel-card title="أبناؤك" subtitle="اضغط للاشتراكات ودفع فودافون كاش.">
            <div class="space-y-3">
                @forelse ($stats['children'] as $child)
                    <a
                        href="{{ route('parent.children.payments', $child['id']) }}"
                        wire:navigate
                        class="block rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-bold text-ink">{{ $child['name'] }}</div>
                                <div class="mt-0.5 font-mono text-xs text-ink-muted" dir="ltr">{{ $child['student_code'] ?? '—' }}</div>
                            </div>
                            <span class="text-sm font-semibold text-brand-700">الاشتراكات ←</span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <div class="text-xs text-ink-muted">نشط</div>
                                <div class="font-bold text-ink">{{ $child['active_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">بانتظار الدفع</div>
                                <div class="font-bold text-ink">{{ $child['pending_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">دروس مكتملة</div>
                                <div class="font-bold text-ink">{{ $child['completed_lessons'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">متوسط الامتحان</div>
                                <div class="font-bold text-ink">
                                    {{ $child['average_exam_score'] !== null ? $child['average_exam_score'].'%' : '—' }}
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-ink-muted">
                        لا يوجد أبناء مرتبطون بعد. أرسل طلب ربط بكود الطالب بالأسفل.
                    </p>
                @endforelse
            </div>
        </x-panel-card>

        <x-panel-card title="ربط ابن بكود الطالب" subtitle="اطلب الكود من لوحة ابنك، ثم أرسل الطلب وانتظر موافقته.">
            <div class="max-w-lg">
                <livewire:parent.request-child-link />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
