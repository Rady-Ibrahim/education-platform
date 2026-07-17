<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">الهيكل الأكاديمي</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900" wire:navigate>
                رجوع للوحة الإدارة
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">المراحل → الصفوف → المواد → الوحدات</h3>
                </div>
                <div class="p-6">
                    <livewire:admin.academic-manager />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">ربط المدرس بالمادة</h3>
                </div>
                <div class="p-6">
                    <livewire:admin.assign-teacher-subject />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">تسجيل الطالب في صف</h3>
                </div>
                <div class="p-6">
                    <livewire:admin.enroll-student-grade />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
