<x-app-layout>
    <x-panel-page title="الاشتراكات والدفع" subtitle="اشترك في خطة مدرسك وأرسل إثبات الدفع عند الحاجة.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="link-brand" target="_blank" rel="noopener">الدروس ↗</a>
            <a href="{{ route('student.exams') }}" class="link-brand" target="_blank" rel="noopener">الامتحانات ↗</a>
        </x-slot:actions>

        <livewire:student.manage-subscriptions />
    </x-panel-page>
</x-app-layout>
