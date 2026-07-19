<div class="space-y-5">
    <x-page-section title="شهاداتك" subtitle="الشهادات الصادرة بعد اجتياز الامتحانات.">
        <div class="space-y-3">
            @forelse ($certificates as $certificate)
                <div class="list-row">
                    <div>
                        <div class="font-semibold text-ink">{{ $certificate->title }}</div>
                        <div class="text-sm text-ink-muted">
                            {{ $certificate->subject?->name }}
                            @if ($certificate->scorePercent() !== null)
                                — {{ $certificate->scorePercent() }}%
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-ink-muted">
                            رقم التحقق: <span class="font-mono">{{ $certificate->verification_code }}</span>
                            — {{ $certificate->issued_at->format('Y-m-d') }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('student.certificates.show', $certificate) }}" class="btn-brand" target="_blank" rel="noopener">عرض / طباعة ↗</a>
                        <a href="{{ $certificate->verifyUrl() }}" target="_blank" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-ink transition hover:bg-slate-50" rel="noopener">صفحة التحقق ↗</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد شهادات بعد. اجتز امتحانًا بدرجة النجاح للحصول على شهادة.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>
</div>
