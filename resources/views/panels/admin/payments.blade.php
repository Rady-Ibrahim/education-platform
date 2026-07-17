<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">مراجعة المدفوعات</h2>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800" wire:navigate>
                لوحة الإدارة
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">طابور المدفوعات المعلّقة</h3>
                    <p class="text-sm text-gray-500 mt-1">إشراف على كل المدفوعات بما فيها ما أكّده المدرسون.</p>
                </div>
                <div class="p-6">
                    <livewire:admin.payment-oversight />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
