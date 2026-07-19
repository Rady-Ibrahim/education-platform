<x-app-layout>
    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forAdmin();
    @endphp

    <x-panel-page title="لوحة الإدارة" subtitle="مؤشرات المنصة — التفاصيل من القوائم.">
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" class="btn-brand" wire:navigate>الحسابات</a>
            <a href="{{ route('admin.payments') }}" class="btn-accent" wire:navigate>المدفوعات</a>
        </x-slot:actions>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-tile label="مدرسون ظاهرون" :value="$stats['public_teachers']" :href="route('teachers.index')">م</x-stat-tile>
            <x-stat-tile label="اشتراكات نشطة" :value="$stats['active_subscriptions']" tone="success">ش</x-stat-tile>
            <x-stat-tile label="مدفوعات معلّقة" :value="$stats['pending_payments']" :href="route('admin.payments')" :tone="$stats['pending_payments'] > 0 ? 'warning' : 'default'">د</x-stat-tile>
            <x-stat-tile label="حسابات موقوفة" :value="$stats['suspended_users']" :href="route('admin.users')" :tone="$stats['suspended_users'] > 0 ? 'danger' : 'default'">ح</x-stat-tile>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-quick-link href="{{ route('admin.academic') }}" title="الهيكل الأكاديمي" description="مراحل وصفوف">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('admin.users') }}" title="الحسابات" description="إيقاف وإشراف">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('admin.payments') }}" title="المدفوعات" description="طابور المراجعة">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </x-quick-link>
            <x-quick-link href="{{ route('admin.platform') }}" title="اشتراك المنصة" description="رسوم المدرسين">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </x-quick-link>
        </div>
    </x-panel-page>
</x-app-layout>
