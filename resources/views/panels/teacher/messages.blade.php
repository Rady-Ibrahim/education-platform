<x-app-layout>
    <x-panel-page title="مراسلة أولياء الأمور" subtitle="ابعث رسالة أو صورة لولي أمر طالب في مجموعتك.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>الطلاب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.message-parents />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
