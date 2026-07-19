<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher(auth()->user());
        $subjects = auth()->user()->teachingSubjects()->orderBy('name')->pluck('name');
        $subjectLabel = $subjects->unique()->implode(' · ') ?: null;
    @endphp

    <x-panel-page
        title="لوحة المدرس"
        :subtitle="$subjectLabel ? 'تدريس: '.$subjectLabel : 'المهام العاجلة والاختصارات فقط.'"
    >
        <x-slot:actions>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" wire:navigate>إضافة طالب</a>
            <a href="{{ route('teacher.exams.manual') }}" class="btn-accent" wire:navigate>درجة يدوية</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="الطلاب" :value="$stats['students_count']" :href="route('teacher.students')">ط</x-stat-tile>
            <x-stat-tile label="التحصيل" :value="number_format($stats['confirmed_total'], 0).' ج.م'" :href="route('teacher.payments', ['tab' => 'cash'])" tone="success">ج</x-stat-tile>
            <x-stat-tile label="مدفوعات معلّقة" :value="$stats['pending_payments']" :href="route('teacher.payments', ['tab' => 'vodafone'])" :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'">م</x-stat-tile>
            <x-stat-tile label="طلبات انضمام" :value="$stats['pending_join_requests']" :href="route('teacher.students.join')" :tone="$stats['pending_join_requests'] > 0 ? 'warning' : 'default'">ط</x-stat-tile>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-quick-link href="{{ route('teacher.groups') }}" title="المجموعات" description="مستويات ومواعيد وأعضاء">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('teacher.students') }}" title="الطلاب" description="قائمة مكتبك">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('teacher.lessons') }}" title="الدروس" description="محتوى ونشر">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('teacher.exams.manual') }}" title="امتحان ورقي" description="رفع ورقة + درجة يدوية">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </x-quick-link>
        </div>
    </x-panel-page>
</x-app-layout>
