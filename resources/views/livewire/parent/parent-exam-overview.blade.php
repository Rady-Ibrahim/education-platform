<div class="space-y-5">
    @forelse ($children as $child)
        <x-page-section :title="$child->name" :subtitle="$child->student_code">
            <x-slot:actions>
                <a href="{{ route('parent.children.exams', $child->id) }}" class="link-brand text-sm">تفاصيل الابن ↗</a>
            </x-slot:actions>

            @php $rows = $attemptsByChild->get($child->id, collect()); @endphp
            @if ($rows->isEmpty())
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد نتائج امتحانات بعد.</p>
                </div>
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
        </x-page-section>
    @empty
        <div class="empty-state">
            <p class="text-sm text-ink-muted">اربط ابنًا أولًا من صفحة ربط ابن.</p>
        </div>
    @endforelse
</div>
