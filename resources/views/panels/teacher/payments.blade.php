<x-app-layout>
    <x-panel-page title="مكتب التحصيل" subtitle="دفتر شهري: مين عليه فلوس · كامل/جزئي/خصم/إيصال — وفودافون من ولي الأمر.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>الطلاب</a>
        </x-slot:actions>

        <x-panel-card :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:teacher.manage-payments />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
