<div>
    <div class="mb-5 grid gap-3 lg:grid-cols-[1.4fr_0.8fr_auto]">
        <div>
            <x-input-label value="بحث عن طالب" />
            <x-text-input wire:model.live.debounce.300ms="search" class="mt-1 block w-full" placeholder="الاسم، الكود، الهاتف، أو الإيميل" />
        </div>
        <div>
            <x-input-label value="حالة الاشتراك" />
            <select wire:model.live="filter" class="mt-1 block w-full">
                <option value="all">الكل</option>
                <option value="active_sub">اشتراك نشط</option>
                <option value="pending_payment">بانتظار الدفع</option>
            </select>
        </div>
        <div class="flex items-end">
            <a href="{{ route('teacher.dashboard') }}" class="btn-brand w-full lg:w-auto" wire:navigate>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                إضافة طالب من المكتب
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الكود</th>
                        <th>الصف</th>
                        <th>الاشتراك</th>
                        <th>الدفع</th>
                        <th>الانتهاء</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $active = ($activeSubs[$student->id] ?? collect());
                            $pendingSub = ($pendingSubs[$student->id] ?? collect());
                            $pendingPay = ($pendingByStudent[$student->id] ?? collect());
                            $latestActive = $active->sortByDesc('ends_at')->first();
                            $grade = $student->grades->first();
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">
                                        {{ mb_substr($student->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-ink">{{ $student->name }}</div>
                                        <div class="text-xs text-ink-muted">{{ $student->phone ?: $student->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs">{{ $student->student_code ?? '—' }}</td>
                            <td class="text-ink-muted">{{ $grade?->name ?? '—' }}</td>
                            <td>
                                @if ($active->isNotEmpty())
                                    <x-status-badge tone="success">نشط</x-status-badge>
                                @elseif ($pendingSub->isNotEmpty())
                                    <x-status-badge tone="warning">بانتظار الدفع</x-status-badge>
                                @else
                                    <x-status-badge tone="neutral">بدون اشتراك</x-status-badge>
                                @endif
                            </td>
                            <td>
                                @if ($pendingPay->isNotEmpty())
                                    <x-status-badge tone="info">مراجعة</x-status-badge>
                                @elseif ($active->isNotEmpty())
                                    <x-status-badge tone="brand">مدفوع</x-status-badge>
                                @elseif ($pendingSub->isNotEmpty())
                                    <x-status-badge tone="danger">متأخر / معلّق</x-status-badge>
                                @else
                                    <x-status-badge tone="neutral">—</x-status-badge>
                                @endif
                            </td>
                            <td class="text-ink-muted">
                                {{ $latestActive?->ends_at?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('teacher.students.show', $student) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900" wire:navigate>
                                    عرض
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-ink-muted">لا يوجد طلاب مطابقون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
