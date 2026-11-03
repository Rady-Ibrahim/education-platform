<x-app-layout>
    <x-panel-page title="إشراف الحسابات" subtitle="إنشاء حسابات المدرسين وإدارة كل الحسابات.">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="link-brand">اللوحة ↗</a>
        </x-slot:actions>

        <x-dashboard-tabs
            :tabs="[
                'moderation' => 'إشراف وتعديل',
                'create'     => 'إنشاء مدرس',
                'pending'    => 'بانتظار الموافقة',
                'link'       => 'ربط ولي أمر',
            ]"
            default="moderation"
        >
            <x-dashboard-panel name="moderation">
                <livewire:admin.user-moderation />
            </x-dashboard-panel>

            <x-dashboard-panel name="create">
                <livewire:admin.create-teacher-form />
            </x-dashboard-panel>

            <x-dashboard-panel name="pending">
                <livewire:admin.pending-approvals />
            </x-dashboard-panel>

            <x-dashboard-panel name="link">
                <livewire:admin.link-parent-student />
            </x-dashboard-panel>
        </x-dashboard-tabs>
    </x-panel-page>
</x-app-layout>
