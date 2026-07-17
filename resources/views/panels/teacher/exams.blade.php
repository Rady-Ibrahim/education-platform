<x-app-layout>
    <x-panel-page title="الامتحانات وبنك الأسئلة" subtitle="أنشئ الامتحانات وصحّح المقالات وتابع النتائج.">
        <x-slot:actions>
            <a href="{{ route('teacher.dashboard') }}" class="link-brand" wire:navigate>مكتب المدرس</a>
        </x-slot:actions>

        <x-panel-card title="بنك الأسئلة والامتحانات">
            <livewire:teacher.manage-exams />
        </x-panel-card>

        <x-panel-card title="تصحيح المقالات">
            <livewire:teacher.grade-exam-attempts />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
