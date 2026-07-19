<x-app-layout>
    <x-panel-page title="الاشتراكات والدفع" subtitle="اشترك في خطة مدرسك وأرسل إثبات الدفع عند الحاجة.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="link-brand">الدروس ↗</a>
            <a href="{{ route('student.exams') }}" class="link-brand">الامتحانات ↗</a>
        </x-slot:actions>

        <livewire:student.manage-subscriptions />
    </x-panel-page>
</x-app-layout>
