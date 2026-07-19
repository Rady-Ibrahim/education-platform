<x-app-layout>
    <x-panel-page title="امتحان ورقي ودرجات يدوية" subtitle="ارفع ورقة الامتحان وسجّل درجة كل طالب بنفسك.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand">الامتحانات الإلكترونية ↗</a>
        </x-slot:actions>

        <livewire:teacher.manual-exam-grades />
    </x-panel-page>
</x-app-layout>
