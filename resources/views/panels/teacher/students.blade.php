<x-app-layout>
    <x-panel-page title="إدارة الطلاب" subtitle="قائمة المجموعة، طلبات الانضمام، وإضافة طالب جديد.">
        <x-slot:actions>
            <a href="#add-student" class="btn-brand">إضافة طالب</a>
            <a href="{{ route('teacher.payments') }}" class="link-brand" wire:navigate>المدفوعات</a>
        </x-slot:actions>

        <div id="join-requests">
            <x-panel-card title="طلبات الانضمام" subtitle="قبول أو رفض الطلاب اللي طلبوا الانضمام لمجموعتك.">
                <livewire:teacher.join-requests />
            </x-panel-card>
        </div>

        <x-panel-card title="طلاب المجموعة" subtitle="بحث وتصفية حسب حالة الاشتراك." :padding="false">
            <div class="p-5 sm:p-6">
                <livewire:teacher.student-desk />
            </div>
        </x-panel-card>

        <div id="add-student">
            <x-panel-card title="إضافة طالب يدويًا" subtitle="ينضم لمجموعتك مباشرة ويتفعّل الحساب.">
                <div class="max-w-xl">
                    <livewire:teacher.create-student-form />
                </div>
            </x-panel-card>
        </div>
    </x-panel-page>
</x-app-layout>
