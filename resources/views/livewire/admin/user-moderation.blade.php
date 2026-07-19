<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="الحسابات" subtitle="إيقاف حساب أو إخفاء مدرس من الكتالوج.">
        <div class="mb-4 flex flex-wrap gap-3">
            <select wire:model.live="filter" class="rounded-xl border-slate-200 text-sm">
                <option value="teachers">المدرسون</option>
                <option value="students">الطلاب</option>
                <option value="parents">أولياء الأمور</option>
                <option value="suspended">الموقوفون</option>
            </select>
            <x-text-input wire:model.live.debounce.300ms="search" class="max-w-xs" placeholder="بحث بالاسم / الإيميل / الهاتف" />
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الحالة</th>
                            <th>الظهور العام</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr wire:key="mod-{{ $user->id }}">
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->status->label() }}</td>
                                <td>
                                    @if ($user->hasRole('teacher'))
                                        {{ $user->is_publicly_visible ? 'ظاهر' : 'مخفي' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="space-x-2 space-x-reverse text-sm">
                                    @if ($user->status === \App\Enums\UserStatus::Suspended)
                                        <button type="button" wire:click="unsuspend({{ $user->id }})" class="link-brand">إعادة تفعيل</button>
                                    @elseif ($user->id !== auth()->id())
                                        <button type="button" wire:click="suspend({{ $user->id }})" class="text-rose-600 hover:underline" wire:confirm="إيقاف هذا الحساب؟">إيقاف</button>
                                    @endif
                                    @if ($user->hasRole('teacher') && $user->is_publicly_visible)
                                        <button type="button" wire:click="hideFromCatalog({{ $user->id }})" class="text-amber-700 hover:underline">إخفاء من الكتالوج</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-ink-muted">لا توجد نتائج.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </x-page-section>
</div>
