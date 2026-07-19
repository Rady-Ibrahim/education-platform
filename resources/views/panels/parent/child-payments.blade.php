<x-app-layout>
    <x-panel-page title="مدفوعات الابن" subtitle="اشتراكات وفودافون كاش لابنك.">
        <x-slot:actions>
            <a href="{{ route('parent.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة ولي الأمر ↗</a>
        </x-slot:actions>

        <livewire:parent.manage-child-payments :student-id="$studentId" />
    </x-panel-page>
</x-app-layout>
