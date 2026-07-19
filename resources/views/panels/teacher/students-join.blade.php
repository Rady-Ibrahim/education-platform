<x-app-layout>
    <x-panel-page title="طلبات الانضمام" subtitle="قبول أو رفض الطلاب اللي طلبوا الانضمام لمجموعتك.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>رجوع للطلاب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.join-requests />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
