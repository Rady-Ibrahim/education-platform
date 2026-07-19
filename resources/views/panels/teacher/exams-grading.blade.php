<x-app-layout>
    <x-panel-page title="تصحيح المقالات" subtitle="راجع إجابات الأسئلة المقالية وامنح الدرجات.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand" wire:navigate>الامتحانات</a>
            <a href="{{ route('teacher.exams.manual') }}" class="link-brand" wire:navigate>درجات يدوية</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.grade-exam-attempts />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
