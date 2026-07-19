<div class="space-y-5">
    @if (session('group_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('group_status') }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <x-page-section
            :title="$editingGroupId ? 'تعديل مجموعة' : 'إنشاء مجموعة'"
            subtitle="كل مستوى يمكن أن يكون له أكثر من مجموعة (مواعيد مختلفة)."
        >
            <div class="space-y-4">
                <div>
                    <x-input-label value="المادة / المستوى" />
                    <select wire:model="subjectId" class="mt-1.5 block w-full" @disabled($editingGroupId)>
                        <option value="">اختر المادة</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} — {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('subjectId')" />
                </div>

                <div>
                    <x-input-label value="اسم المجموعة" />
                    <x-text-input wire:model="name" class="mt-1.5 block w-full" placeholder="سبت واتنين / جمعة / صباحي" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label value="الموعد (اختياري)" />
                    <x-text-input wire:model="scheduleNote" class="mt-1.5 block w-full" placeholder="5م — 7م" />
                    <x-input-error :messages="$errors->get('scheduleNote')" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-primary-button type="button" wire:click="save">
                        {{ $editingGroupId ? 'حفظ التعديل' : 'إنشاء المجموعة' }}
                    </x-primary-button>
                    @if ($editingGroupId)
                        <x-secondary-button type="button" wire:click="cancelEdit">إلغاء</x-secondary-button>
                    @endif
                </div>
            </div>
        </x-page-section>

        <x-page-section title="مجموعاتك" subtitle="فلتر حسب المستوى ثم افتح الأعضاء.">
            <x-slot:actions>
                <div class="min-w-[12rem]">
                    <x-input-label value="المستوى" />
                    <select wire:model.live="filterGradeId" class="mt-1.5 block w-full">
                        <option value="">كل المستويات</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
            </x-slot:actions>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>المجموعة</th>
                            <th>المستوى</th>
                            <th>مستمرون</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr @class(['bg-brand-50/40' => $manageGroupId === $group->id])>
                                <td>
                                    <div class="font-medium text-ink">{{ $group->name }}</div>
                                    @if ($group->schedule_note)
                                        <div class="text-xs text-ink-muted">{{ $group->schedule_note }}</div>
                                    @endif
                                    @unless ($group->is_active)
                                        <div class="mt-1 text-xs font-medium text-amber-700">متوقفة</div>
                                    @endunless
                                </td>
                                <td class="text-sm">{{ $group->grade?->name }}</td>
                                <td class="text-sm">{{ $group->active_students_count }}</td>
                                <td class="space-x-2 space-x-reverse text-start text-sm">
                                    <button type="button" class="link-brand" wire:click="openMembers({{ $group->id }})">الأعضاء</button>
                                    <button type="button" class="link-brand" wire:click="edit({{ $group->id }})">تعديل</button>
                                    <button type="button" class="text-slate-600 hover:text-ink" wire:click="toggleActive({{ $group->id }})">
                                        {{ $group->is_active ? 'إيقاف' : 'تفعيل' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="text-rose-600 hover:text-rose-700"
                                        wire:click="deleteGroup({{ $group->id }})"
                                        wire:confirm="حذف المجموعة؟ لن يُحذف الطلاب من مكتبك."
                                    >حذف</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-ink-muted">
                                    لا توجد مجموعات بعد — أنشئ أول مجموعة للمستوى.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-page-section>
    </div>

    @if ($manageGroup)
        <x-page-section
            :title="'أعضاء: '.$manageGroup->displayLabel()"
            :subtitle="($manageGroup->subject?->name ?? '').' — حالة الطالب: مستمر / متوقف / مجمد.'"
        >
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <x-input-label value="طالب من مكتبك" />
                        <select wire:model="addStudentId" class="mt-1.5 block w-full">
                            <option value="">اختر طالبًا</option>
                            @foreach ($availableStudents as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->name }}
                                    @if ($student->student_code) — {{ $student->student_code }} @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('addStudentId')" />
                    </div>
                    <div>
                        <x-input-label value="الحالة" />
                        <select wire:model="addStatus" class="mt-1.5 block w-full">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button type="button" wire:click="addMember" class="w-full sm:w-auto">ضم للمجموعة</x-primary-button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الحالة</th>
                                <th>انضم</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($members as $member)
                                <tr>
                                    <td>
                                        <div class="font-medium">{{ $member->name }}</div>
                                        @if ($member->student_code)
                                            <div class="text-xs text-ink-muted">{{ $member->student_code }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <select
                                            class="block w-full text-sm"
                                            wire:change="setMemberStatus({{ $member->id }}, $event.target.value)"
                                        >
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->value }}" @selected($member->pivot->status === $status->value)>
                                                    {{ $status->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-sm text-ink-muted">
                                        {{ $member->pivot->joined_at ? \Illuminate\Support\Carbon::parse($member->pivot->joined_at)->format('Y-m-d') : '—' }}
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="text-sm text-rose-600 hover:text-rose-700"
                                            wire:click="removeMember({{ $member->id }})"
                                            wire:confirm="إزالة الطالب من المجموعة؟"
                                        >إزالة</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-ink-muted">لا أعضاء في هذه المجموعة بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-page-section>
    @endif
</div>
