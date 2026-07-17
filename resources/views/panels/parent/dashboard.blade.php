<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            لوحة ولي الأمر
        </h2>
    </x-slot>

    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">إشعارات غير مقروءة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['unread_notifications'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">متابعة الأبناء</div>
                    <div class="text-sm text-gray-600 mt-2">ربط ولي الأمر بالأبناء سيُفعَّل في تحديث لاحق.</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    مرحبًا {{ auth()->user()->name }} — الإشعارات الحرجة تظهر من جرس الإشعارات أعلاه.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
