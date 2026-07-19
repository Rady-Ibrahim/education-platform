<x-app-layout>
    <x-panel-page title="إضافة طالب" subtitle="إنشاء حساب طالب وربطه بمجموعتك، مع ربط ولي الأمر.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand">رجوع للطلاب ↗</a>
        </x-slot:actions>

        <div class="grid gap-5 lg:grid-cols-2">
            <livewire:teacher.create-student-form />
            <livewire:teacher.link-parent-to-student />
        </div>
    </x-panel-page>
</x-app-layout>
