<x-app-layout>
    <x-panel-page title="الطلاب" subtitle="بحث وتصفية ومتابعة الاشتراك والدفع — نظّم الطلاب داخل المجموعات.">
        <x-slot:actions>
            <a href="{{ route('teacher.groups') }}" class="link-brand" wire:navigate>المجموعات</a>
            <a href="{{ route('teacher.students.join') }}" class="link-brand" wire:navigate>طلبات الانضمام</a>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" wire:navigate>إضافة طالب</a>
        </x-slot:actions>

        <x-panel-card :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:teacher.student-desk />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
