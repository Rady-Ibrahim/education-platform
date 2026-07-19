<x-app-layout>
    <x-panel-page title="الامتحانات الإلكترونية" subtitle="بنك الأسئلة وإنشاء الامتحانات الأونلاين.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams.grading') }}" class="link-brand" wire:navigate>تصحيح المقالات</a>
            <a href="{{ route('teacher.exams.manual') }}" class="btn-brand" wire:navigate>امتحان ورقي / يدوي</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.manage-exams />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
