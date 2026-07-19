<x-app-layout>
    <x-panel-page title="امتحاناتي" subtitle="ادخل الامتحانات المتاحة وشوف نتيجتك.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة الطالب ↗</a>
        </x-slot:actions>

        <livewire:student.take-exam />
    </x-panel-page>
</x-app-layout>
