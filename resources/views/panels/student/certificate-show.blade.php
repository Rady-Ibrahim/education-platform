<x-app-layout>
    <x-panel-page title="شهادة" subtitle="يمكنك طباعة الشهادة أو الرجوع للقائمة." class="print:py-0">
        <x-slot:actions>
            <button type="button" onclick="window.print()" class="link-brand print:hidden">طباعة</button>
            <a href="{{ route('student.certificates') }}" class="link-brand print:hidden" wire:navigate>رجوع</a>
        </x-slot:actions>

        <div class="mx-auto max-w-3xl space-y-6 rounded-3xl border-2 border-brand-900 bg-white p-10 text-center shadow-panel">
            <div class="text-sm font-semibold tracking-wide text-brand-700">{{ config('app.name', 'سنتر') }}</div>
            <h1 class="text-3xl font-bold text-ink">شهادة اجتياز</h1>
            <p class="text-lg text-ink-soft">يُشهد بأن الطالب</p>
            <p class="text-2xl font-semibold text-ink">{{ $certificate->student->name }}</p>
            <p class="text-ink-soft">قد اجتاز بنجاح</p>
            <p class="text-xl font-medium text-ink">{{ $certificate->title }}</p>
            @if ($certificate->subject)
                <p class="text-ink-muted">المادة: {{ $certificate->subject->name }}</p>
            @endif
            @if ($certificate->scorePercent() !== null)
                <p class="text-ink-muted">الدرجة: {{ $certificate->scorePercent() }}%</p>
            @endif
            <div class="space-y-1 border-t border-brand-100 pt-6 text-sm text-ink-muted">
                <div>رقم التحقق: <span class="font-mono text-ink">{{ $certificate->verification_code }}</span></div>
                <div>تاريخ الإصدار: {{ $certificate->issued_at->format('Y-m-d') }}</div>
                <div class="break-all">تحقق عبر: {{ $certificate->verifyUrl() }}</div>
            </div>
        </div>
    </x-panel-page>
</x-app-layout>
