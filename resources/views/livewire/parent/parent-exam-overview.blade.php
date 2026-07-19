<div class="space-y-6">
    @forelse ($children as $child)
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-soft">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-ink">{{ $child->name }}</h3>
                    <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $child->student_code }}</div>
                </div>
                <a href="{{ route('parent.children.exams', $child->id) }}" class="link-brand text-sm" wire:navigate>تفاصيل الابن</a>
            </div>

            @php $rows = $attemptsByChild->get($child->id, collect()); @endphp
            @if ($rows->isEmpty())
                <p class="text-sm text-ink-muted">لا توجد نتائج امتحانات بعد.</p>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الامتحان</th>
                                <th>المادة</th>
                                <th>الدرجة</th>
                                <th>النسبة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $attempt)
                                @php
                                    $percent = $attempt->max_score > 0
                                        ? round(((float) $attempt->score / (float) $attempt->max_score) * 100, 1)
                                        : null;
                                @endphp
                                <tr>
                                    <td class="font-semibold">{{ $attempt->exam?->title }}</td>
                                    <td>{{ $attempt->exam?->subject?->name }}</td>
                                    <td>{{ $attempt->score }} / {{ $attempt->max_score }}</td>
                                    <td>
                                        @if ($percent !== null)
                                            <span @class(['font-bold', 'text-emerald-700' => $percent >= 50, 'text-rose-700' => $percent < 50])>{{ $percent }}%</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-ink-muted">{{ $attempt->submitted_at?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @empty
        <p class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-ink-muted">
            اربط ابنًا أولًا من صفحة ربط ابن.
        </p>
    @endforelse
</div>
