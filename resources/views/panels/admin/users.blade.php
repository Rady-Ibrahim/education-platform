<x-app-layout>
    <x-panel-page title="إشراف الحسابات" subtitle="إيقاف حساب أو إخفاء مدرس من الكتالوج.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="link-brand">اللوحة ↗</a>
        </x-slot:actions>

        <div class="space-y-5">
            <livewire:admin.user-moderation />
            <livewire:admin.link-parent-student />
        </div>
    </x-panel-page>
</x-app-layout>
