<x-app-layout>
    <x-panel-page title="تصحيح المقالات" subtitle="راجع إجابات الأسئلة المقالية وامنح الدرجات.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand">الامتحانات ↗</a>
            <a href="{{ route('teacher.exams.manual') }}" class="link-brand">درجات يدوية ↗</a>
        </x-slot:actions>

        <livewire:teacher.grade-exam-attempts />
    </x-panel-page>
</x-app-layout>
