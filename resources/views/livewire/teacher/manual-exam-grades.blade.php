<div class="space-y-8">
    @if (session('manual_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('manual_status') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="space-y-4">
            <div>
                <h3 class="text-sm font-bold text-ink">إنشاء امتحان ورقي</h3>
                <p class="mt-0.5 text-xs text-ink-muted">ارفع ورقة الامتحان (PDF/صورة) وسجّل الدرجات يدويًا بعد التصحيح.</p>
            </div>

            <div>
                <x-input-label value="المادة" />
                <select wire:model.live="subjectId" class="mt-1.5 block w-full">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label value="عنوان الامتحان" />
                <x-text-input wire:model="paperTitle" class="mt-1.5 block w-full" placeholder="امتحان الشهر — ورقى" />
                <x-input-error :messages="$errors->get('paperTitle')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="الدرجة النهائية" />
                    <x-text-input wire:model="manualMaxScore" type="number" step="0.5" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('manualMaxScore')" />
                </div>
                <div>
                    <x-input-label value="درجة النجاح %" />
                    <x-text-input wire:model="passScore" type="number" step="0.5" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('passScore')" />
                </div>
            </div>

            <div>
                <x-input-label value="ملف الورقة (اختياري)" />
                <input type="file" wire:model="paperFile" accept=".pdf,image/*" class="mt-1.5 block w-full text-sm">
                <div wire:loading wire:target="paperFile" class="mt-1 text-xs text-brand-700">جاري الرفع…</div>
                <x-input-error :messages="$errors->get('paperFile')" />
            </div>

            <x-primary-button type="button" wire:click="createPaperExam">حفظ الامتحان الورقي</x-primary-button>
        </section>

        <section class="space-y-4">
            <div>
                <h3 class="text-sm font-bold text-ink">تسجيل درجة طالب</h3>
                <p class="mt-0.5 text-xs text-ink-muted">بعد تصحيح الورقة، أدخل الدرجة يدويًا.</p>
            </div>

            <div>
                <x-input-label value="الامتحان" />
                <select wire:model="gradeExamId" class="mt-1.5 block w-full">
                    <option value="">اختر امتحانًا ورقيًا</option>
                    @foreach ($paperExams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->title }} (من {{ $exam->manual_max_score }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('gradeExamId')" />
            </div>

            <div>
                <x-input-label value="الطالب" />
                <select wire:model="gradeStudentId" class="mt-1.5 block w-full">
                    <option value="">اختر طالبًا</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} @if($student->student_code) — {{ $student->student_code }} @endif</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('gradeStudentId')" />
            </div>

            <div>
                <x-input-label value="الدرجة" />
                <x-text-input wire:model="gradeScore" type="number" step="0.5" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('gradeScore')" />
            </div>

            <x-primary-button type="button" wire:click="saveManualGrade">حفظ الدرجة</x-primary-button>
        </section>
    </div>

    <section>
        <h3 class="mb-3 text-sm font-bold text-ink">آخر الدرجات اليدوية</h3>
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الامتحان</th>
                        <th>الدرجة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentGrades as $grade)
                        <tr>
                            <td>{{ $grade->student?->name }}</td>
                            <td>{{ $grade->exam?->title }}</td>
                            <td class="font-semibold">{{ $grade->score }} / {{ $grade->max_score }}</td>
                            <td class="text-ink-muted">{{ $grade->submitted_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-ink-muted">لا توجد درجات يدوية بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
