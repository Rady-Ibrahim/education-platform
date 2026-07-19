<x-app-layout>
    <x-panel-page title="طلبات الانضمام" subtitle="قبول أو رفض الطلاب اللي طلبوا الانضمام لمجموعتك.">
        <x-slot:actions>
            <a href="{{ route('teacher.students') }}" class="link-brand" target="_blank" rel="noopener">رجوع للطلاب ↗</a>
        </x-slot:actions>

        <livewire:teacher.join-requests />
    </x-panel-page>
</x-app-layout>
