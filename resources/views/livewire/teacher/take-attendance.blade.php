<div class="space-y-5">
    @if (session('attendance_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('attendance_status') }}
        </div>
    @endif

    <x-page-section title="بيانات الحصة" subtitle="اختر المجموعة والتاريخ قبل تسجيل الكشف.">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <x-input-label value="المجموعة" />
                <select wire:model.live="groupId" class="mt-1.5 block w-full">
                    <option value="">اختر مجموعة الحصة</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">
                            {{ $group->grade?->name }} / {{ $group->name }}
                            @if ($group->schedule_note) — {{ $group->schedule_note }} @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('groupId')" />
            </div>
            <div>
                <x-input-label value="تاريخ الحصة" />
                <x-text-input wire:model.live="sessionDate" type="date" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('sessionDate')" />
            </div>
            <div>
                <x-input-label value="ملاحظة الحصة (اختياري)" />
                <x-text-input wire:model="sessionNote" class="mt-1.5 block w-full" placeholder="مراجعة / امتحان…" />
            </div>
        </div>
    </x-page-section>

    @if ($groups->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center shadow-soft">
            <p class="text-sm text-ink-muted">أنشئ مجموعة أولًا قبل تسجيل الحضور.</p>
            <a href="{{ route('teacher.groups') }}" class="btn-brand mt-4">إنشاء مجموعة ↗</a>
        </div>
    @elseif (! $groupId)
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-ink-muted shadow-soft">
            اختر المجموعة لعرض كشف الحضور.
        </div>
    @elseif ($roster->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-ink-muted shadow-soft">
            لا يوجد طلاب مستمرون في هذه المجموعة.
            <a href="{{ route('teacher.groups') }}" class="link-brand ms-1">إدارة الأعضاء ↗</a>
        </div>
    @else
        <x-page-section
            :title="'كشف '.($selectedGroup?->displayLabel() ?? '')"
            subtitle="اضغط الحالة لكل طالب ثم احفظ — زي كشف الورق."
        >
            <x-slot:actions>
                <x-secondary-button type="button" wire:click="markAllPresent">الكل حاضر</x-secondary-button>
                <x-secondary-button type="button" wire:click="markAllAbsent">الكل غائب</x-secondary-button>
                <x-primary-button type="button" wire:click="save">حفظ الحضور</x-primary-button>
            </x-slot:actions>

            @if ($summary)
                <div class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-emerald-800">حاضر {{ $summary['present'] }}</span>
                    <span class="rounded-lg bg-rose-50 px-2.5 py-1 text-rose-800">غائب {{ $summary['absent'] }}</span>
                    <span class="rounded-lg bg-amber-50 px-2.5 py-1 text-amber-900">متأخر {{ $summary['late'] }}</span>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-700">بعذر {{ $summary['excused'] }}</span>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roster as $student)
                            <tr>
                                <td>
                                    <div class="font-medium text-ink">{{ $student['name'] }}</div>
                                    @if ($student['student_code'])
                                        <div class="font-mono text-xs text-ink-muted" dir="ltr">{{ $student['student_code'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($statuses as $status)
                                            @php
                                                $active = ($marks[$student['id']] ?? '') === $status->value;
                                                $tone = match ($status) {
                                                    \App\Enums\AttendanceStatus::Present => $active ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100',
                                                    \App\Enums\AttendanceStatus::Absent => $active ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100',
                                                    \App\Enums\AttendanceStatus::Late => $active ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-900 hover:bg-amber-100',
                                                    \App\Enums\AttendanceStatus::Excused => $active ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                                                };
                                            @endphp
                                            <button
                                                type="button"
                                                wire:click="$set('marks.{{ $student['id'] }}', '{{ $status->value }}')"
                                                class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition {{ $tone }}"
                                            >
                                                {{ $status->label() }}
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <x-primary-button type="button" wire:click="save">حفظ الحضور</x-primary-button>
            </div>
            <x-input-error :messages="$errors->get('marks')" />
        </x-page-section>
    @endif
</div>
