<x-app-layout>
    <x-panel-page title="شهاداتي" subtitle="الشهادات الصادرة بعد اجتياز الامتحانات.">
        <x-slot:actions>
            <a href="{{ route('student.exams') }}" class="link-brand" wire:navigate>الامتحانات</a>
            <a href="{{ route('student.dashboard') }}" class="link-brand" wire:navigate>لوحة الطالب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:student.my-certificates />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
