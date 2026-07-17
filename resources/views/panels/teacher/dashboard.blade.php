<x-app-layout>
    <x-panel-page title="لوحة التحكم" subtitle="مرحبًا {{ auth()->user()->name }} — نظرة سريعة على نشاط مجموعتك اليوم.">
        <x-slot:actions>
            <a href="{{ route('teacher.payments') }}" class="btn-accent" wire:navigate>
                تسجيل دفع
            </a>
            <a href="{{ route('teacher.lessons') }}" class="btn-brand" wire:navigate>
                إضافة درس
            </a>
        </x-slot:actions>

        @php
            $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher(auth()->user());
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">إجمالي الطلاب</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['students_count'] }}</div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">التحصيل المؤكد</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ number_format($stats['confirmed_total'], 0) }} <span class="text-base font-semibold text-ink-muted">ج.م</span></div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">مدفوعات معلّقة</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['pending_payments'] }}</div>
                    <div class="mt-1 text-xs text-amber-700">تحتاج مراجعة</div>
                </div>
            </div>
            <div class="surface-stat">
                <div class="ps-3">
                    <div class="text-sm text-ink-muted">متأخرون (+3 أيام)</div>
                    <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['late_subscriptions'] }}</div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <x-panel-card title="طلاب مجموعتك" subtitle="إدارة سريعة مع البحث والتصفية.">
                <x-slot:actions>
                    <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>عرض الكل</a>
                </x-slot:actions>
                <livewire:teacher.student-desk />
            </x-panel-card>

            <div class="space-y-6">
                <x-panel-card title="طلبات الانضمام">
                    <livewire:teacher.join-requests />
                </x-panel-card>

                <x-panel-card title="إضافة طالب يدويًا" subtitle="يتفعّل وينضم لمجموعتك مباشرة.">
                    <livewire:teacher.create-student-form />
                </x-panel-card>
            </div>
        </div>

        <x-panel-card title="موادك الدراسية">
            <ul class="grid gap-2 sm:grid-cols-2">
                @php
                    $subjects = app(\App\Modules\Academic\Services\AcademicStructureService::class)
                        ->subjectsForTeacher(auth()->user());
                @endphp
                @forelse ($subjects as $subject)
                    <li class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-ink">
                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                    </li>
                @empty
                    <li class="text-sm text-ink-muted">حدّد مادتك من <a href="{{ route('profile') }}" class="link-brand" wire:navigate>البروفايل</a>.</li>
                @endforelse
            </ul>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
