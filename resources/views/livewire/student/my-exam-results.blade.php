<x-page-section title="نتائجي" subtitle="درجاتك في الامتحانات المُسلَّمة — النسبة وحالة النجاح.">
    <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الامتحان</th>
                    <th>المادة</th>
                    <th>تاريخ التسليم</th>
                    <th>الدرجة</th>
                    <th>النسبة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr wire:key="result-{{ $row->attempt->id }}">
                        <td class="font-semibold">{{ $row->attempt->exam?->title }}</td>
                        <td class="text-ink-muted">{{ $row->attempt->exam?->subject?->name }}</td>
                        <td class="text-xs text-ink-muted" dir="ltr">
                            {{ $row->attempt->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="font-mono tabular-nums">
                            {{ number_format($row->summary['score'], 1) }} / {{ number_format($row->summary['max_score'], 1) }}
                        </td>
                        <td class="font-mono tabular-nums font-bold">{{ number_format($row->summary['percent'], 1) }}%</td>
                        <td>
                            @if (! is_null($row->summary['passed']))
                                @if ($row->summary['passed'])
                                    <x-status-badge tone="success">ناجح</x-status-badge>
                                @else
                                    <x-status-badge tone="danger">رسوب</x-status-badge>
                                @endif
                            @else
                                <x-status-badge tone="neutral">مُصحَّح</x-status-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-ink-muted">لا توجد نتائج بعد — ابدأ بأحد الامتحانات المتاحة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page-section>
