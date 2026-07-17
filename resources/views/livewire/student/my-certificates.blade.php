<div>
    <div class="space-y-4">
        @forelse ($certificates as $certificate)
            <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-medium">{{ $certificate->title }}</div>
                    <div class="text-sm text-gray-600">
                        {{ $certificate->subject?->name }}
                        @if ($certificate->scorePercent() !== null)
                            — {{ $certificate->scorePercent() }}%
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        رقم التحقق: <span class="font-mono">{{ $certificate->verification_code }}</span>
                        — {{ $certificate->issued_at->format('Y-m-d') }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('student.certificates.show', $certificate) }}" class="inline-flex items-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                        عرض / طباعة
                    </a>
                    <a href="{{ $certificate->verifyUrl() }}" target="_blank" class="inline-flex items-center rounded-xl border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-ink transition hover:bg-brand-50">
                        صفحة التحقق
                    </a>
                </div>
            </div>
        @empty
            <p class="text-gray-600">لا توجد شهادات بعد. اجتز امتحانًا بدرجة النجاح للحصول على شهادة.</p>
        @endforelse
    </div>
</div>
