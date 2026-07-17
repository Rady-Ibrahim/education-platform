<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">لوحة الإدارة</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('admin.academic') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    الهيكل الأكاديمي
                </a>
                <a href="{{ route('admin.payments') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                    المدفوعات
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
                    <div class="text-sm text-gray-500">حسابات بانتظار الموافقة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['pending_approvals'] }}</div>
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
                    <div class="text-sm text-gray-500">دروس مكتملة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['completed_lessons'] }}</div>
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
                    <h3 class="font-medium text-gray-900">طلبات الموافقة على الحسابات</h3>
                    <p class="text-sm text-gray-500 mt-1">طلاب ومدرسون وأولياء أمور سجّلوا بأنفسهم وينتظروا التفعيل.</p>
                </div>
                <div class="p-6">
                    <livewire:admin.pending-approvals />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">ربط ولي أمر بطالب</h3>
                    <p class="text-sm text-gray-500 mt-1">ربط مباشر نشط بدون انتظار موافقة الطالب.</p>
                </div>
                <div class="p-6">
                    <livewire:admin.link-parent-student />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
