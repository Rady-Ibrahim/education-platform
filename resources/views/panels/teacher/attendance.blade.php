<x-app-layout>
    <x-panel-page title="حضور اليوم" subtitle="اختر المجموعة والتاريخ — سجّل حاضر / غائب / متأخر / بعذر في ثواني.">
        <x-slot:actions>
            <a href="{{ route('teacher.groups') }}" class="link-brand" target="_blank" rel="noopener">المجموعات ↗</a>
            <a href="{{ route('teacher.messages') }}" class="btn-accent" target="_blank" rel="noopener">بلّغ ولي الأمر ↗</a>
        </x-slot:actions>

        <livewire:teacher.take-attendance />
    </x-panel-page>
</x-app-layout>
