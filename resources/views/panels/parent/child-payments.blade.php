<x-app-layout>
    <x-panel-page title="مدفوعات الابن" subtitle="اشتراكات وفودافون كاش لابنك.">
        <x-slot:actions>
            <a href="{{ route('parent.dashboard') }}" class="link-brand" wire:navigate>لوحة ولي الأمر</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:parent.manage-child-payments :student-id="$studentId" />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
