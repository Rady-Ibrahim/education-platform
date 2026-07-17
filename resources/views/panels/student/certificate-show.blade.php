<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between print:hidden">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">شهادة</h2>
            <div class="flex gap-3 text-sm">
                <button type="button" onclick="window.print()" class="text-indigo-600 hover:text-indigo-800">طباعة</button>
                <a href="{{ route('student.certificates') }}" class="text-gray-600 hover:text-gray-800" wire:navigate>رجوع</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 print:py-0">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border-2 border-gray-800 shadow-sm sm:rounded-lg p-10 text-center space-y-6">
                <div class="text-sm tracking-widest text-gray-500 uppercase">Education Platform</div>
                <h1 class="text-3xl font-bold text-gray-900">شهادة اجتياز</h1>
                <p class="text-lg text-gray-700">يُشهد بأن الطالب</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $certificate->student->name }}</p>
                <p class="text-gray-700">قد اجتاز بنجاح</p>
                <p class="text-xl font-medium text-gray-900">{{ $certificate->title }}</p>
                @if ($certificate->subject)
                    <p class="text-gray-600">المادة: {{ $certificate->subject->name }}</p>
                @endif
                @if ($certificate->scorePercent() !== null)
                    <p class="text-gray-600">الدرجة: {{ $certificate->scorePercent() }}%</p>
                @endif
                <div class="pt-6 border-t text-sm text-gray-500 space-y-1">
                    <div>رقم التحقق: <span class="font-mono text-gray-800">{{ $certificate->verification_code }}</span></div>
                    <div>تاريخ الإصدار: {{ $certificate->issued_at->format('Y-m-d') }}</div>
                    <div class="break-all">تحقق عبر: {{ $certificate->verifyUrl() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
