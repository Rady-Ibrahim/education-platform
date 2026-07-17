<x-app-layout>
    <x-panel-page title="امتحاناتي" subtitle="ادخل الامتحانات المتاحة وشوف نتيجتك.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand" wire:navigate>لوحة الطالب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:student.take-exam />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
