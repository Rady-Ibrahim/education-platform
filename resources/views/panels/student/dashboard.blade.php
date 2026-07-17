<x-app-layout>
    <x-panel-page title="لوحتي" subtitle="مرحبًا {{ auth()->user()->name }} — تابع دروسك واشتراكاتك من هنا.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="btn-brand" wire:navigate>ابدأ التعلم</a>
            <a href="{{ route('teachers.index') }}" class="btn-accent" wire:navigate>تصفّح المدرسين</a>
        </x-slot:actions>

        @php
            $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent(auth()->user());
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">اشتراكات نشطة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['active_subscriptions'] }}</div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">بانتظار الدفع</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['pending_subscriptions'] }}</div>
                    @if ($stats['pending_subscriptions'] > 0)
                        <a href="{{ route('student.subscriptions') }}" class="mt-1 inline-flex text-xs link-brand" wire:navigate>إكمال الدفع</a>
                    @endif
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">دروس مكتملة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['completed_lessons'] }}</div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">إشعارات</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['unread_notifications'] }}</div>
                </div>
            </div>
        </div>

        @if (auth()->user()->student_code)
            <div class="rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-brand-800">كود الطالب لولي الأمر</div>
                        <p class="mt-1 text-xs text-brand-700">شارك الكود مع ولي أمرك للربط والدفع بفودافون كاش.</p>
                    </div>
                    <div class="font-mono text-2xl font-bold tracking-wide text-brand-950">{{ auth()->user()->student_code }}</div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <x-panel-card title="اختصارات سريعة">
                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('student.lessons') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50" wire:navigate>
                        <div class="font-bold text-ink">الدروس</div>
                        <div class="mt-1 text-sm text-ink-muted">شاهد وتابع تقدّمك</div>
                    </a>
                    <a href="{{ route('student.exams') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50" wire:navigate>
                        <div class="font-bold text-ink">الامتحانات</div>
                        <div class="mt-1 text-sm text-ink-muted">ادخل الاختبارات المتاحة</div>
                    </a>
                    <a href="{{ route('student.subscriptions') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50" wire:navigate>
                        <div class="font-bold text-ink">الاشتراكات</div>
                        <div class="mt-1 text-sm text-ink-muted">الخطط وإثبات الدفع</div>
                    </a>
                    <a href="{{ route('student.certificates') }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50" wire:navigate>
                        <div class="font-bold text-ink">الشهادات</div>
                        <div class="mt-1 text-sm text-ink-muted">شهادات الاجتياز</div>
                    </a>
                </div>
            </x-panel-card>

            <x-panel-card title="مدرسوك">
                <ul class="space-y-2">
                    @forelse (auth()->user()->teachers as $teacher)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                            <div>
                                <div class="font-semibold text-ink">{{ $teacher->name }}</div>
                                <div class="text-xs text-ink-muted">{{ $teacher->headline ?: $teacher->email }}</div>
                            </div>
                            @if ($teacher->slug)
                                <a href="{{ route('teachers.show', $teacher->slug) }}" class="text-sm font-semibold text-brand-700" wire:navigate>عرض</a>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-ink-muted">
                            لسه منضمّتش لأي مدرس.
                            <a href="{{ route('teachers.index') }}" class="mt-2 block link-brand" wire:navigate>تصفّح المدرسين</a>
                        </li>
                    @endforelse
                </ul>
            </x-panel-card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-panel-card title="طلب الانضمام لمدرس" subtitle="ابعت طلب وانتظر موافقة المدرس، أو خلّيه يضيفك من مكتبه.">
                <livewire:student.request-teacher-join />
            </x-panel-card>

            <x-panel-card title="أولياء الأمور" subtitle="وافق على طلبات الربط أو ألغِ الربط الحالي.">
                <livewire:student.parent-link-requests />
            </x-panel-card>
        </div>
    </x-panel-page>
</x-app-layout>
