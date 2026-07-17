<x-app-layout>
    <x-panel-page title="إدارة الدروس" subtitle="أضف دروس النص والفيديو ونشرها لطلابك.">
        <x-slot:actions>
            <a href="{{ route('teacher.dashboard') }}" class="link-brand" wire:navigate>مكتب المدرس</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.manage-lessons />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
