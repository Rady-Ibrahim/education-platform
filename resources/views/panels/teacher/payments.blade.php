<x-app-layout>
    <x-panel-page title="مكتب التحصيل" subtitle="دفتر شهري · فودافون · خطط — كل قسم في تاب منفصل داخل الصفحة.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" target="_blank" rel="noopener">الطلاب ↗</a>
        </x-slot:actions>

        <livewire:teacher.manage-payments />
    </x-panel-page>
</x-app-layout>
