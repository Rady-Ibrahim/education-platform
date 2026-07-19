<x-app-layout>
    <x-panel-page title="دروسي" subtitle="شاهد الدروس وتابع تقدّمك.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة الطالب ↗</a>
        </x-slot:actions>

        <livewire:student.browse-lessons />
    </x-panel-page>
</x-app-layout>
