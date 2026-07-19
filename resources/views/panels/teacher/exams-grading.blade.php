<x-app-layout>
    <x-panel-page title="تصحيح المقالات" subtitle="راجع إجابات الأسئلة المقالية وامنح الدرجات.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand" target="_blank" rel="noopener">الامتحانات ↗</a>
            <a href="{{ route('teacher.exams.manual') }}" class="link-brand" target="_blank" rel="noopener">درجات يدوية ↗</a>
        </x-slot:actions>

        <livewire:teacher.grade-exam-attempts />
    </x-panel-page>
</x-app-layout>
