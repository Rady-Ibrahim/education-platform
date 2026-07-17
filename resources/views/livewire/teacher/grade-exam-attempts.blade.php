<div>
    @if (session('grade_status'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('grade_status') }}
        </div>
    @endif

    <div class="mb-4">
        <h3 class="text-base font-bold text-ink">تصحيح الأسئلة المقالية</h3>
        <p class="mt-1 text-sm text-ink-muted">راجع إجابات الطلاب وأدخل الدرجة يدويًا.</p>
    </div>

    <div class="space-y-4">
        @forelse ($answers as $answer)
            @php
                $key = $answer->question_id.'-'.$answer->attempt_id;
                $max = (float) ($answer->attempt->exam->questions->firstWhere('id', $answer->question_id)?->pivot?->points
                    ?? $answer->question->points);
            @endphp
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-semibold text-ink">{{ $answer->attempt->student?->name }}</div>
                        <div class="text-sm text-ink-muted">{{ $answer->attempt->exam?->title }}</div>
                    </div>
                    <x-status-badge tone="info">الحد الأقصى {{ $max }}</x-status-badge>
                </div>

                <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-ink">
                    <div class="mb-2 font-medium text-ink-muted">السؤال</div>
                    <p class="whitespace-pre-line">{{ $answer->question->stem }}</p>
                </div>

                <div class="mt-3 rounded-xl border border-brand-100 bg-brand-50/40 p-3 text-sm text-ink">
                    <div class="mb-2 font-medium text-brand-800">إجابة الطالب</div>
                    <p class="whitespace-pre-line">{{ $answer->answer_text ?: '—' }}</p>
                </div>

                <div class="mt-3 flex flex-wrap items-end gap-3">
                    <div class="w-36">
                        <x-input-label value="الدرجة" />
                        <x-text-input type="number" step="0.5" min="0" max="{{ $max }}" wire:model="pointsInput.{{ $key }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('pointsInput.'.$key)" class="mt-1" />
                    </div>
                    <x-primary-button type="button" wire:click="grade({{ $answer->attempt_id }}, {{ $answer->question_id }})">
                        حفظ الدرجة
                    </x-primary-button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-ink-muted">
                لا توجد إجابات مقالية للتصحيح حاليًا.
            </div>
        @endforelse
    </div>
</div>
