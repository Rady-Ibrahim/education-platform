<x-app-layout>
    <x-panel-page title="إشراف الحسابات" subtitle="إيقاف حساب أو إخفاء مدرس من الكتالوج.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand" wire:navigate>اللوحة</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:admin.user-moderation />
        </x-panel-card>

        <x-panel-card title="ربط ولي أمر بطالب" subtitle="ربط مباشر نشط عند الحاجة.">
            <livewire:admin.link-parent-student />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
