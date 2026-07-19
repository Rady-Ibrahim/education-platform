<x-app-layout>
    <x-panel-page title="نتائج الامتحانات" subtitle="درجات ابنك في الامتحانات الإلكترونية والورقية.">
        <x-slot:actions>
            <a href="{{ route('parent.dashboard') }}" class="link-brand">لوحة ولي الأمر ↗</a>
            <a href="{{ route('parent.children.payments', $studentId) }}" class="link-brand">المدفوعات ↗</a>
        </x-slot:actions>

        <livewire:parent.child-exam-results :student-id="$studentId" />
    </x-panel-page>
</x-app-layout>
