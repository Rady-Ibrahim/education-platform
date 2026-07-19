<x-app-layout>
    <x-panel-page title="شهاداتي" subtitle="الشهادات الصادرة بعد اجتياز الامتحانات.">
        <x-slot:actions>
            <a href="{{ route('student.exams') }}" class="link-brand" target="_blank" rel="noopener">الامتحانات ↗</a>
            <a href="{{ route('student.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة الطالب ↗</a>
        </x-slot:actions>

        <livewire:student.my-certificates />
    </x-panel-page>
</x-app-layout>
