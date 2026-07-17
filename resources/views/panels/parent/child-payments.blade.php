<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مدفوعات الابن</h2>
            <a href="{{ route('parent.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800" wire:navigate>لوحة ولي الأمر</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <livewire:parent.manage-child-payments :student-id="$studentId" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
