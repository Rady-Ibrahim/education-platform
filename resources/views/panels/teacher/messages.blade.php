<x-app-layout>
    <x-panel-page title="مراسلة أولياء الأمور" subtitle="ابعث رسالة أو صورة لولي أمر طالب في مجموعتك.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand">الطلاب ↗</a>
        </x-slot:actions>

        <livewire:teacher.message-parents />
    </x-panel-page>
</x-app-layout>
