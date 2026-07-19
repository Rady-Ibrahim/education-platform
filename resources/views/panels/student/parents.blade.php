<x-app-layout>
    <x-panel-page title="أولياء الأمور" subtitle="وافق على طلبات الربط أو ألغِ الربط الحالي.">
        <x-slot:actions>
            <a href="{{ route('student.dashboard') }}" class="link-brand">لوحتي ↗</a>
        </x-slot:actions>

        <livewire:student.parent-link-requests />
    </x-panel-page>
</x-app-layout>
