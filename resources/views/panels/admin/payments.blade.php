<x-app-layout>
    <x-panel-page title="مراجعة المدفوعات" subtitle="إشراف على المدفوعات المعلّقة والمؤكدة.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand" wire:navigate>لوحة الإدارة</a>
        </x-slot:actions>

        <x-panel-card title="طابور المدفوعات المعلّقة" subtitle="يشمل ما أكّده المدرسون وما ينتظر المراجعة.">
            <livewire:admin.payment-oversight />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
