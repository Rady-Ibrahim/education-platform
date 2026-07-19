<x-app-layout>
    <x-panel-page title="إدارة الدروس" subtitle="أضف دروس النص والفيديو وانشرها لطلابك.">
        <x-slot:actions>
            <a href="{{ route('teacher.dashboard') }}" class="link-brand">مكتب المدرس ↗</a>
        </x-slot:actions>

        <livewire:teacher.manage-lessons />
    </x-panel-page>
</x-app-layout>
