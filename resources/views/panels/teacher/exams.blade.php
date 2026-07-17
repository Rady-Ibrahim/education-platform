<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">الامتحانات وبنك الأسئلة</h2>
            <a href="{{ route('teacher.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900" wire:navigate>مكتب المدرس</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <livewire:teacher.manage-exams />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
