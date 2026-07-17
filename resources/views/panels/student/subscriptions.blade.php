<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">الاشتراكات والدفع</h2>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('student.lessons') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الدروس</a>
                <a href="{{ route('student.exams') }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>الامتحانات</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">اشتراكاتك وإثبات الدفع</h3>
                    <p class="text-sm text-gray-500 mt-1">اشترك في خطة مدرسك ثم أرسل إثبات فودافون كاش للمراجعة.</p>
                </div>
                <div class="p-6">
                    <livewire:student.manage-subscriptions />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
