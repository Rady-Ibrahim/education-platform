<div class="space-y-5">
    <x-page-section
        title="نتائج: {{ $exam->title }}"
        subtitle="{{ $exam->subject?->name }} — درجة النجاح: {{ $stats['pass_score'] !== null ? $stats['pass_score'].'%' : 'غير محددة' }}"
    >
        <x-slot:actions>
            <a href="{{ route('teacher.exams') }}" class="link-brand">عودة للامتحانات ↗</a>
        </x-slot:actions>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="surface-stat">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">عدد الطلاب</div>
                <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['students'] }}</div>
                <div class="mt-1 text-xs text-ink-muted">أكملوا: {{ $stats['completed'] }} ({{ $stats['completion_rate'] }}%)</div>
            </div>
            <div class="surface-stat">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">متوسط الدرجات %</div>
                <div class="mt-2 text-3xl font-bold text-ink">{{ $stats['average'] ?? '—' }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">أعلى / أدنى %</div>
                <div class="mt-2 text-xl font-bold text-emerald-600">{{ $stats['highest'] ?? '—' }} <span class="text-slate-300">/</span> <span class="text-rose-500">{{ $stats['lowest'] ?? '—' }}</span></div>
            </div>
            <div class="surface-stat">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-muted">نسبة النجاح</div>
                <div class="mt-2 text-3xl font-bold text-brand-700">{{ $stats['pass_rate'] !== null ? $stats['pass_rate'].'%' : '—' }}</div>
            </div>
        </div>
    </x-page-section>

    <x-page-section title="محاولات الطلاب" subtitle="الدرجة والنسبة والإجابات الصحيحة/الخاطئة/بدون إجابة لكل طالب.">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الحالة</th>
                        <th>الدرجة</th>
                        <th>%</th>
                        <th>صحيحة</th>
                        <th>خاطئة</th>
                        <th>بدون إجابة</th>
                        <th>وقت التسليم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="attempt-{{ $row->attempt->id }}">
                            <td>
                                <div class="font-semibold">{{ $row->attempt->student?->name }}</div>
                                @if ($row->attempt->student?->student_code)
                                    <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $row->attempt->student->student_code }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($row->attempt->status->value === 'in_progress')
                                    <x-status-badge tone="warning">جارٍ</x-status-badge>
                                @else
                                    @if (! is_null($row->summary['passed']))
                                        @if ($row->summary['passed'])
                                            <x-status-badge tone="success">ناجح</x-status-badge>
                                        @else
                                            <x-status-badge tone="danger">رسوب</x-status-badge>
                                        @endif
                                    @else
                                        <x-status-badge tone="neutral">{{ $row->attempt->status->label() }}</x-status-badge>
                                    @endif
                                @endif
                            </td>
                            <td class="font-mono tabular-nums font-bold">
                                {{ number_format($row->summary['score'], 1) }} / {{ number_format($row->summary['max_score'], 1) }}
                            </td>
                            <td class="font-mono tabular-nums">{{ number_format($row->summary['percent'], 1) }}%</td>
                            <td><span class="rounded-lg bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600">{{ $row->summary['correct'] }}</span></td>
                            <td><span class="rounded-lg bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-500">{{ $row->summary['wrong'] }}</span></td>
                            <td><span class="rounded-lg bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-600">{{ $row->summary['unanswered'] }}</span></td>
                            <td class="text-xs text-ink-muted" dir="ltr">
                                {{ $row->attempt->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-ink-muted">لا توجد محاولات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-page-section>
</div>
