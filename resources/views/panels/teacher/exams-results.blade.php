<x-app-layout>
    <x-panel-page title="نتائج الامتحان" subtitle="درجات الطلاب وإحصائيات الامتحان.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand">الامتحانات ↗</a>
            <a href="{{ route('teacher.exams.grading') }}" class="link-brand">تصحيح المقالات ↗</a>
        </x-slot:actions>

        <livewire:teacher.exam-results :exam-id="$examId" />
    </x-panel-page>
</x-app-layout>
