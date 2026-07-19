<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
    @endphp

    <x-panel-page title="لوحة ولي الأمر" subtitle="تابع أبناءك — النتائج والمدفوعات من صفحة كل ابن.">
        <x-slot:actions>
            <a href="{{ route('parent.link') }}" class="btn-brand" wire:navigate>ربط ابن</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-stat-tile label="الأبناء" :value="$stats['children_count']" tone="success">أ</x-stat-tile>
            <x-stat-tile label="إشعارات" :value="$stats['unread_notifications']" :tone="$stats['unread_notifications'] > 0 ? 'warning' : 'default'">إ</x-stat-tile>
        </div>

        <x-panel-card title="أبناؤك">
            <div class="space-y-3">
                @forelse ($stats['children'] as $child)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-bold text-ink">{{ $child['name'] }}</div>
                                <div class="mt-0.5 font-mono text-xs text-ink-muted" dir="ltr">{{ $child['student_code'] ?? '—' }}</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('parent.children.exams', $child['id']) }}" class="btn-brand !px-3 !py-2 text-xs" wire:navigate>النتائج</a>
                                <a href="{{ route('parent.children.payments', $child['id']) }}" class="btn-accent !px-3 !py-2 text-xs" wire:navigate>المدفوعات</a>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <div class="text-xs text-ink-muted">اشتراك نشط</div>
                                <div class="font-bold">{{ $child['active_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">بانتظار الدفع</div>
                                <div class="font-bold">{{ $child['pending_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">دروس مكتملة</div>
                                <div class="font-bold">{{ $child['completed_lessons'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-ink-muted">متوسط الدرجات</div>
                                <div class="font-bold">{{ $child['average_exam_score'] !== null ? $child['average_exam_score'].'%' : '—' }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-ink-muted">
                        لا يوجد أبناء مرتبطون.
                        <a href="{{ route('parent.link') }}" class="mt-2 block link-brand" wire:navigate>اربط ابنًا بكود الطالب</a>
                    </p>
                @endforelse
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
