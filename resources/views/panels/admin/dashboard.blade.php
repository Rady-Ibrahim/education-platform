<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forAdmin();
    @endphp

    <x-panel-page title="لوحة الإدارة" subtitle="مؤشرات المنصة والمهام الإشرافية.">
        <x-slot:actions>
            <a href="{{ route('admin.payments') }}" class="btn-brand" wire:navigate>المدفوعات</a>
            <a href="{{ route('admin.platform') }}" class="btn-accent" wire:navigate>اشتراك المنصة</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="مدرسون ظاهرون" :value="$stats['public_teachers']" :href="route('teachers.index')">م</x-stat-tile>
            <x-stat-tile label="اشتراكات نشطة" :value="$stats['active_subscriptions']" tone="success">ش</x-stat-tile>
            <x-stat-tile
                label="مدفوعات معلّقة"
                :value="$stats['pending_payments']"
                :href="route('admin.payments')"
                :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'"
            >د</x-stat-tile>
            <x-stat-tile
                label="حسابات موقوفة"
                :value="$stats['suspended_users']"
                :tone="$stats['suspended_users'] > 0 ? 'danger' : 'default'"
            >ح</x-stat-tile>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">إجمالي المحصّل</div>
                <div class="mt-2 text-2xl font-bold text-ink">{{ number_format($stats['confirmed_payments_total'], 0) }} <span class="text-base text-ink-muted">ج.م</span></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">متوسط درجات الامتحانات</div>
                <div class="mt-2 text-2xl font-bold text-ink">
                    {{ $stats['average_exam_score'] !== null ? $stats['average_exam_score'].'%' : '—' }}
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-quick-link href="{{ route('admin.academic') }}" title="الهيكل الأكاديمي" description="مراحل وصفوف ومواد">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('admin.payments') }}" title="مدفوعات الطلاب" description="مراجعة الطابور">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('admin.platform') }}" title="اشتراك المنصة" description="رسوم المدرسين">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('teachers.index') }}" title="كتالوج المدرسين" description="الصفحة العامة">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </x-quick-link>
        </div>

        <x-panel-card title="إشراف الحسابات" subtitle="إيقاف حساب أو إخفاء مدرس من الكتالوج عند الحاجة.">
            <livewire:admin.user-moderation />
        </x-panel-card>

        <details class="surface-panel group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="section-title">ربط ولي أمر بطالب</h2>
                    <p class="section-subtitle">ربط مباشر — غالبًا المدرس يعمله من مكتبه</p>
                </div>
                <span class="text-xs font-semibold text-brand-700 group-open:hidden">إظهار</span>
                <span class="hidden text-xs font-semibold text-brand-700 group-open:inline">إخفاء</span>
            </summary>
            <div class="p-5 sm:p-6">
                <livewire:admin.link-parent-student />
            </div>
        </details>
    </x-panel-page>
</x-app-layout>
