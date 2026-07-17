<x-app-layout>
    <x-panel-page title="كشف حساب الطالب" subtitle="الاشتراكات والمدفوعات المرتبطة بالطالب.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>رجوع للطلاب</a>
        </x-slot:actions>

        <x-panel-card>
            <livewire:teacher.student-account-statement :student-id="$studentId" />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
