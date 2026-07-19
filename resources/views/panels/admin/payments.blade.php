<x-app-layout>
    <x-panel-page title="مراجعة المدفوعات" subtitle="إشراف على المدفوعات المعلّقة والمؤكدة.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة الإدارة ↗</a>
        </x-slot:actions>

        <livewire:admin.payment-oversight />
    </x-panel-page>
</x-app-layout>
