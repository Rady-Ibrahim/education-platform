<x-app-layout>
    <x-panel-page title="امتحاناتي" subtitle="ادخل الامتحانات المتاحة وشوف نتيجتك.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand">لوحة الطالب ↗</a>
        </x-slot:actions>

        <livewire:student.take-exam />

        <livewire:student.my-exam-results />
    </x-panel-page>
</x-app-layout>
