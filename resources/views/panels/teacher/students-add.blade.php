<x-app-layout>
    <x-panel-page title="إضافة طالب" subtitle="إنشاء حساب طالب وربطه بمجموعتك مباشرة.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" wire:navigate>رجوع للطلاب</a>
        </x-slot:actions>

        <x-panel-card>
            <div class="max-w-xl">
                <livewire:teacher.create-student-form />
            </div>
        </x-panel-card>

        <x-panel-card title="ربط ولي أمر بطالب" subtitle="اربط ولي أمر بكود الطالب أو بحسابه.">
            <div class="max-w-xl">
                <livewire:teacher.link-parent-to-student />
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
