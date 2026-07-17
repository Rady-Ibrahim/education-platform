<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">لوحة الطالب</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('student.lessons') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الدروس</a>
                <a href="{{ route('student.exams') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الامتحانات</a>
                <a href="{{ route('student.subscriptions') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الاشتراكات</a>
                <a href="{{ route('student.certificates') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الشهادات</a>
            </div>
        </div>
    </x-slot>

    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forStudent(auth()->user());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">اشتراكات نشطة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['active_subscriptions'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">بانتظار الدفع</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['pending_subscriptions'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">دروس مكتملة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['completed_lessons'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">إشعارات غير مقروءة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['unread_notifications'] }}</div>
                </div>
            </div>

            @if (auth()->user()->student_code)
                <div class="bg-indigo-50 border border-indigo-100 shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-indigo-700">كود الطالب لولي الأمر</div>
                    <div class="text-2xl font-mono font-semibold mt-1 text-indigo-900">{{ auth()->user()->student_code }}</div>
                    <p class="text-xs text-indigo-600 mt-1">شارك هذا الكود مع ولي أمرك ليربط حسابه بحسابك.</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-medium mb-3">صفك الدراسي</h3>
                    <ul class="space-y-2">
                        @forelse (auth()->user()->grades as $grade)
                            <li class="text-sm text-gray-700">{{ $grade->stage?->name }} / {{ $grade->name }}</li>
                        @empty
                            <li class="text-sm text-gray-500">لسه متعيّنش على صف. الإدارة هتسجّلك قريب.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">طلب الانضمام لمدرس</h3>
                    <p class="text-sm text-gray-500 mt-1">بعد موافقة الإدارة على حسابك، ابعت طلب للمدرس اللي عايز تنضم له وانتظر موافقته.</p>
                </div>
                <div class="p-6">
                    <livewire:student.request-teacher-join />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">أولياء الأمور</h3>
                    <p class="text-sm text-gray-500 mt-1">وافق على طلبات الربط أو ألغِ الربط الحالي.</p>
                </div>
                <div class="p-6">
                    <livewire:student.parent-link-requests />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-medium mb-3">مدرسوك</h3>
                    <ul class="space-y-2">
                        @forelse (auth()->user()->teachers as $teacher)
                            <li class="text-sm text-gray-700">{{ $teacher->name }} — {{ $teacher->email }}</li>
                        @empty
                            <li class="text-sm text-gray-500">لسه منضمّتش لأي مدرس.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
