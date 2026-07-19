<div class="space-y-5">
    <x-page-section :title="'نتائج '.$studentName" subtitle="الامتحانات الإلكترونية والورقية.">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الامتحان</th>
                        <th>المادة</th>
                        <th>النوع</th>
                        <th>الدرجة</th>
                        <th>النسبة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        @php
                            $percent = $attempt->max_score > 0
                                ? round(((float) $attempt->score / (float) $attempt->max_score) * 100, 1)
                                : null;
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ $attempt->exam?->title }}</td>
                            <td>{{ $attempt->exam?->subject?->name }}</td>
                            <td>{{ $attempt->exam?->isPaper() ? 'ورقي' : 'إلكتروني' }}</td>
                            <td>{{ $attempt->score }} / {{ $attempt->max_score }}</td>
                            <td>
                                @if ($percent !== null)
                                    <span @class([
                                        'font-bold',
                                        'text-emerald-700' => $percent >= 50,
                                        'text-rose-700' => $percent < 50,
                                    ])>{{ $percent }}%</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-ink-muted">{{ $attempt->submitted_at?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-ink-muted">لا توجد نتائج امتحانات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $attempts->links() }}</div>
    </x-page-section>
</div>
