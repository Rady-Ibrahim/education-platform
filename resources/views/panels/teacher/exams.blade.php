<x-app-layout>
    <x-panel-page title="الامتحانات الإلكترونية" subtitle="بنك الأسئلة وإنشاء الامتحانات الأونلاين — كل قسم في بطاقة واضحة.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams.grading') }}" class="link-brand" target="_blank" rel="noopener">تصحيح المقالات ↗</a>
            <a href="{{ route('teacher.exams.manual') }}" class="btn-brand" target="_blank" rel="noopener">امتحان ورقي / يدوي ↗</a>
        </x-slot:actions>

        <livewire:teacher.manage-exams />
    </x-panel-page>
</x-app-layout>
