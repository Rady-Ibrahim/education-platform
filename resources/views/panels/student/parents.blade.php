<x-app-layout>
    <x-panel-page title="أولياء الأمور" subtitle="وافق على طلبات الربط أو ألغِ الربط الحالي.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand" wire:navigate>لوحتي</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:student.parent-link-requests />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
