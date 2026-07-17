<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مكتب المدرس</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('teacher.lessons') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الدروس</a>
                <a href="{{ route('teacher.exams') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الامتحانات</a>
                <a href="{{ route('teacher.payments') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>المدفوعات</a>
            </div>
        </div>
    </x-slot>

    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forTeacher(auth()->user());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">عدد الطلاب</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['students_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">التحصيل المؤكد</div>
                    <div class="text-2xl font-semibold mt-1">{{ number_format($stats['confirmed_total'], 2) }} ج.م</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">مدفوعات معلّقة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['pending_payments'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">متأخرون (+3 أيام)</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['late_subscriptions'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">متوسط درجات المجموعة</div>
                    <div class="text-2xl font-semibold mt-1">
                        {{ $stats['average_exam_score'] !== null ? $stats['average_exam_score'].'%' : '—' }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">موادك الدراسية</h3>
                    <p class="text-sm text-gray-500 mt-1">المواد اللي الإدارة ربطتك بيها فقط.</p>
                </div>
                <div class="p-6">
                    <ul class="space-y-2">
                        @php
                            $subjects = app(\App\Modules\Academic\Services\AcademicStructureService::class)
                                ->subjectsForTeacher(auth()->user());
                        @endphp
                        @forelse ($subjects as $subject)
                            <li class="text-sm text-gray-700 border rounded-md px-3 py-2">
                                {{ $subject->grade?->stage?->name }}
                                /
                                {{ $subject->grade?->name }}
                                /
                                {{ $subject->name }}
                                <span class="text-gray-400">({{ $subject->units->count() }} وحدة)</span>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">لا توجد مواد مربوطة بحسابك بعد. تواصل مع الإدارة.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">إضافة طالب يدويًا</h3>
                    <p class="text-sm text-gray-500 mt-1">الحساب يتفعّل مباشرة وينضم لمجموعتك بدون انتظار الأدمن.</p>
                </div>
                <div class="p-6">
                    <livewire:teacher.create-student-form />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">طلبات انضمام الطلاب</h3>
                </div>
                <div class="p-6">
                    <livewire:teacher.join-requests />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">ربط ولي أمر بطالب</h3>
                    <p class="text-sm text-gray-500 mt-1">لطلاب مجموعتك فقط — الربط ينشط فورًا.</p>
                </div>
                <div class="p-6">
                    <livewire:teacher.link-parent-to-student />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-medium mb-1">طلاب مجموعتك</h3>
                        <p class="text-sm text-gray-500">بحث، تصفية، وكشف حساب لكل طالب.</p>
                    </div>
                    <a href="{{ route('teacher.students') }}" class="text-sm text-indigo-600 hover:text-indigo-800" wire:navigate>
                        فتح إدارة الطلاب
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
