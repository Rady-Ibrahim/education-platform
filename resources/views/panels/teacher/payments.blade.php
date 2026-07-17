<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">المدفوعات ومكتب التحصيل</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('teacher.dashboard') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>المكتب</a>
                <a href="{{ route('teacher.lessons') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الدروس</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <livewire:teacher.manage-payments />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
