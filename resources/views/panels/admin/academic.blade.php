<x-app-layout>
    <x-panel-page title="الهيكل الأكاديمي" subtitle="المراحل والصفوف والمواد والوحدات.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand" wire:navigate>لوحة الإدارة</a>
        </x-slot:actions>

        <x-panel-card title="المراحل → الصفوف → المواد → الوحدات">
            <livewire:admin.academic-manager />
        </x-panel-card>

        <x-panel-card title="ربط المدرس بالمادة">
            <livewire:admin.assign-teacher-subject />
        </x-panel-card>

        <x-panel-card title="تسجيل الطالب في صف">
            <livewire:admin.enroll-student-grade />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
