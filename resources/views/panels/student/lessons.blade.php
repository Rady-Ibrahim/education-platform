<x-app-layout>
    <x-panel-page title="دروسي" subtitle="شاهد الدروس وتابع تقدّمك.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand" wire:navigate>لوحة الطالب</a>
        </x-slot:actions>

        <x-panel-card :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:student.browse-lessons />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
