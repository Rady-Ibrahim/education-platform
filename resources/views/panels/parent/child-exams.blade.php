<x-app-layout>
    <x-panel-page title="نتائج الامتحانات" subtitle="درجات ابنك في الامتحانات الإلكترونية والورقية.">
        <x-slot:actions>
            <a href="{{ route('parent.dashboard') }}" class="link-brand" target="_blank" rel="noopener">لوحة ولي الأمر ↗</a>
            <a href="{{ route('parent.children.payments', $studentId) }}" class="link-brand" target="_blank" rel="noopener">المدفوعات ↗</a>
        </x-slot:actions>

        <livewire:parent.child-exam-results :student-id="$studentId" />
    </x-panel-page>
</x-app-layout>
