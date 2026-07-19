<x-app-layout>
    <x-panel-page title="مكتب التحصيل" subtitle="اشتراك شهري — كاش نهاية الشهر في السنتر، أو فودافون من ولي الأمر.">
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
