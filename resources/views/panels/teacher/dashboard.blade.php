<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مكتب المدرس</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('teacher.lessons') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الدروس</a>
                <a href="{{ route('teacher.exams') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الامتحانات</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                <div class="p-6">
                    <h3 class="font-medium mb-3">طلابك الحاليون</h3>
                    <ul class="space-y-2">
                        @forelse (auth()->user()->students as $student)
                            <li class="text-sm text-gray-700">
                                {{ $student->name }}
                                — {{ $student->email }}
                                @if ($student->student_code)
                                    — {{ $student->student_code }}
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">لا يوجد طلاب بعد.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
