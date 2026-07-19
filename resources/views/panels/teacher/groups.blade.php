<x-app-layout>
    <x-panel-page title="المجموعات" subtitle="كل مستوى له مجموعات متعددة — مواعيد، أعضاء، وحالة كل طالب.">
        <x-slot:actions>
            <a href="{{ route('teacher.attendance') }}" class="link-brand" target="_blank" rel="noopener">حضور اليوم ↗</a>
            <a href="{{ route('teacher.students') }}" class="link-brand" target="_blank" rel="noopener">كل الطلاب ↗</a>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand" target="_blank" rel="noopener">إضافة طالب ↗</a>
        </x-slot:actions>

        <livewire:teacher.manage-groups />
    </x-panel-page>
</x-app-layout>
