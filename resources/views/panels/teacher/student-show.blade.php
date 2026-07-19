<x-app-layout>
    <x-panel-page title="كشف حساب الطالب" subtitle="الاشتراكات والمدفوعات المرتبطة بالطالب.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" target="_blank" rel="noopener">رجوع للطلاب ↗</a>
        </x-slot:actions>

        <livewire:teacher.student-account-statement :student-id="$studentId" />
    </x-panel-page>
</x-app-layout>
