<x-app-layout>
    <x-panel-page title="المجموعات" subtitle="كل مستوى له مجموعات متعددة — مواعيد، أعضاء، وحالة كل طالب.">
        <x-slot:actions>
            <a href="{{ route('teacher.attendance') }}" class="link-brand">حضور اليوم ↗</a>
            <a href="{{ route('teacher.students') }}" class="link-brand">كل الطلاب ↗</a>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand">إضافة طالب ↗</a>
        </x-slot:actions>

        <livewire:teacher.manage-groups />
    </x-panel-page>
</x-app-layout>
