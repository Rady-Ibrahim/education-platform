<x-app-layout>
    <x-panel-page title="المجموعات" subtitle="كل مستوى له مجموعات متعددة — مواعيد، أعضاء، وحالة كل طالب.">
        <x-slot:actions>
            <a href="{{ route('teacher.attendance') }}" class="link-brand" wire:navigate>حضور اليوم</a>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>كل الطلاب</a>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" wire:navigate>إضافة طالب</a>
        </x-slot:actions>

        <x-panel-card :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:teacher.manage-groups />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
