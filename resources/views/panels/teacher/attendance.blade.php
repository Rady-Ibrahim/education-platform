<x-app-layout>
    <x-panel-page title="حضور اليوم" subtitle="اختر المجموعة والتاريخ — سجّل حاضر / غائب / متأخر / بعذر في ثواني.">
        <x-slot:actions>
            <a href="{{ route('teacher.groups') }}" class="link-brand">المجموعات ↗</a>
            <a href="{{ route('teacher.messages') }}" class="btn-accent">بلّغ ولي الأمر ↗</a>
        </x-slot:actions>

        <livewire:teacher.take-attendance />
    </x-panel-page>
</x-app-layout>
