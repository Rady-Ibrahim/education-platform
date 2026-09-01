<x-app-layout>
    <x-panel-page title="دروسي" subtitle="تابع دروسك وتقدّمك في كل وحدة.">
        <x-slot:actions>
            <a href="{{ route('student.exams') }}" wire:navigate class="btn-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                الامتحانات
            </a>
        </x-slot:actions>

        <livewire:student.browse-lessons />
    </x-panel-page>
</x-app-layout>
