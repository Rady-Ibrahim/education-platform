<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">لوحة الإدارة — {{ config('app.name', 'سنتر') }}</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('admin.academic') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    الهيكل الأكاديمي
                </a>
                <a href="{{ route('admin.payments') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    مدفوعات الطلاب
                </a>
                <a href="{{ route('admin.platform') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    اشتراك المنصة
                </a>
                <a href="{{ route('teachers.index') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    كتالوج المدرسين
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forAdmin();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">مدرسون ظاهرون للعامة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['public_teachers'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">حسابات موقوفة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['suspended_users'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">اشتراكات نشطة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['active_subscriptions'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">مدفوعات معلّقة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['pending_payments'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">إجمالي المحصّل</div>
                    <div class="text-2xl font-semibold mt-1">{{ number_format($stats['confirmed_payments_total'], 2) }} ج.م</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">متوسط درجات الامتحانات</div>
                    <div class="text-2xl font-semibold mt-1">
                        {{ $stats['average_exam_score'] !== null ? $stats['average_exam_score'].'%' : '—' }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium text-gray-900">إشراف الحسابات</h3>
                    <p class="text-sm text-gray-500 mt-1">التسجيل يتفعّل فورًا. هنا توقف أو تخفي مدرسًا من الكتالوج عند الحاجة — مش موافقة يومية.</p>
                </div>
                <div class="p-6">
                    <livewire:admin.user-moderation />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">ربط ولي أمر بطالب</h3>
                    <p class="text-sm text-gray-500 mt-1">ربط مباشر نشط. في الأغلب المدرس يعمل ده من مكتبه بعد إضافة الطالب في السنتر.</p>
                </div>
                <div class="p-6">
                    <livewire:admin.link-parent-student />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
