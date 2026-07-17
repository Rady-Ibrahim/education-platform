<x-app-layout>
    <x-panel-page title="إدارة الطلاب" subtitle="ابحث وصفِّ طلاب مجموعتك وتابع الاشتراك والدفع.">
        <x-slot:actions>
            <a href="{{ route('teacher.dashboard') }}" class="btn-accent" wire:navigate>إضافة طالب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.student-desk />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
