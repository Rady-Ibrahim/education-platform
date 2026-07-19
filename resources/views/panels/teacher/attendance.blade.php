<x-app-layout>
    <x-panel-page title="حضور اليوم" subtitle="اختر المجموعة والتاريخ — سجّل حاضر / غائب / متأخر / بعذر في ثواني.">
        <x-slot:actions>
            <a href="{{ route('teacher.groups') }}" class="link-brand" wire:navigate>المجموعات</a>
            <a href="{{ route('teacher.messages') }}" class="btn-accent" wire:navigate>بلّغ ولي الأمر</a>
        </x-slot:actions>

        <x-panel-card :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:teacher.take-attendance />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
