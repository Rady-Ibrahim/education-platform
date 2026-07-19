<x-app-layout>
    <x-panel-page title="الهيكل الأكاديمي" subtitle="المراحل والصفوف والمواد والوحدات.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة الإدارة ↗</a>
        </x-slot:actions>

        <div class="space-y-5">
            <livewire:admin.academic-manager />
            <livewire:admin.assign-teacher-subject />
            <livewire:admin.enroll-student-grade />
        </div>
    </x-panel-page>
</x-app-layout>
