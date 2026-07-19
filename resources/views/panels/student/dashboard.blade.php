<x-app-layout>
    @php
        $user = auth()->user();
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent($user);
        $teachers = $user->teachers;
    @endphp

    <x-panel-page title="لوحتي" subtitle="ملخص سريع — التفاصيل من القوائم الجانبية.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="btn-brand" wire:navigate>ابدأ التعلم</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="اشتراكات نشطة" :value="$stats['active_subscriptions']" :href="route('student.subscriptions')" tone="success">ن</x-stat-tile>
            <x-stat-tile label="بانتظار الدفع" :value="$stats['pending_subscriptions']" :href="route('student.subscriptions')" :tone="$stats['pending_subscriptions'] > 0 ? 'warning' : 'default'">د</x-stat-tile>
            <x-stat-tile label="دروس مكتملة" :value="$stats['completed_lessons']" :href="route('student.lessons')">د</x-stat-tile>
            <x-stat-tile label="إشعارات" :value="$stats['unread_notifications']" :tone="$stats['unread_notifications'] > 0 ? 'warning' : 'default'">إ</x-stat-tile>
        </div>

        @if ($user->student_code)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-soft">
                <div>
                    <div class="text-sm font-bold text-ink">كود ولي الأمر</div>
                    <p class="mt-0.5 text-xs text-ink-muted">شاركه مع ولي أمرك.</p>
                </div>
                <div class="rounded-xl bg-brand-50 px-4 py-2 font-mono text-2xl font-bold tracking-widest text-brand-950" dir="ltr">
                    {{ $user->student_code }}
                </div>
            </div>
        @endif

        @if ($teachers->isEmpty())
            <x-panel-card title="انضم لمدرس" subtitle="ابعت طلب وانتظر الموافقة.">
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
                            @if ($teacher->slug)
                                <a href="{{ route('teachers.show', $teacher->slug) }}" class="text-sm font-semibold text-brand-700" wire:navigate>عرض</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-panel-card>
        @endif
    </x-panel-page>
</x-app-layout>
