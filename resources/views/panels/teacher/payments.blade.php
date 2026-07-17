<x-app-layout>
    <x-panel-page title="المدفوعات ومكتب التحصيل" subtitle="خطط الاشتراك، تسجيل الكاش، ومراجعة فودافون كاش.">
        <x-slot:actions>
            <a href="{{ route('teacher.dashboard') }}" class="link-brand" wire:navigate>المكتب</a>
            <a href="{{ route('teacher.lessons') }}" class="link-brand" wire:navigate>الدروس</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.manage-payments />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
