<x-app-layout>
    <x-panel-page title="شهاداتي" subtitle="الشهادات الصادرة بعد اجتياز الامتحانات.">
        <x-slot:actions>
            <a href="{{ route('student.exams') }}" class="link-brand">الامتحانات ↗</a>
            <a href="{{ route('student.dashboard') }}" class="link-brand">لوحة الطالب ↗</a>
        </x-slot:actions>

        <livewire:student.my-certificates />
    </x-panel-page>
</x-app-layout>
