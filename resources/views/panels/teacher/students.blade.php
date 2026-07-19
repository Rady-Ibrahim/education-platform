<x-app-layout>
    <x-panel-page title="الطلاب" subtitle="بحث وتصفية ومتابعة الاشتراك والدفع — نظّم الطلاب داخل المجموعات.">
        <x-slot:actions>
            <a href="{{ route('teacher.groups') }}" class="link-brand">المجموعات ↗</a>
            <a href="{{ route('teacher.students.join') }}" class="link-brand">طلبات الانضمام ↗</a>
            <a href="{{ route('teacher.students.add') }}" class="btn-brand">إضافة طالب ↗</a>
        </x-slot:actions>

        <livewire:teacher.student-desk />
    </x-panel-page>
</x-app-layout>
