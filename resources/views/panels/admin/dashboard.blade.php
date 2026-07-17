<x-app-layout>
    <x-panel-page title="لوحة الإدارة" subtitle="إشراف الحسابات والمدفوعات واشتراك المنصة.">
        <x-slot:actions>
            <a href="{{ route('admin.academic') }}" class="link-brand" wire:navigate>الهيكل الأكاديمي</a>
            <a href="{{ route('admin.payments') }}" class="link-brand" wire:navigate>مدفوعات الطلاب</a>
            <a href="{{ route('admin.platform') }}" class="link-brand" wire:navigate>اشتراك المنصة</a>
            <a href="{{ route('teachers.index') }}" class="link-brand" wire:navigate>كتالوج المدرسين</a>
        </x-slot:actions>

        @php
            $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forAdmin();
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">مدرسون ظاهرون للعامة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['public_teachers'] }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">حسابات موقوفة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['suspended_users'] }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">اشتراكات نشطة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['active_subscriptions'] }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">مدفوعات معلّقة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['pending_payments'] }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">إجمالي المحصّل</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ number_format($stats['confirmed_payments_total'], 2) }} ج.م</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">متوسط درجات الامتحانات</div>
                <div class="mt-1 text-2xl font-bold text-ink">
                    {{ $stats['average_exam_score'] !== null ? $stats['average_exam_score'].'%' : '—' }}
                </div>
            </div>
        </div>

        <x-panel-card title="إشراف الحسابات" subtitle="إيقاف أو إخفاء من الكتالوج عند الحاجة — مش موافقة يومية.">
            <livewire:admin.user-moderation />
        </x-panel-card>

        <x-panel-card title="ربط ولي أمر بطالب" subtitle="ربط مباشر نشط. غالبًا المدرس يعمله من مكتبه.">
            <livewire:admin.link-parent-student />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
