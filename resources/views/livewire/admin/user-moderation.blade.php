<div>
    @if (session('status'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="filter" class="rounded-md border-gray-300 text-sm">
            <option value="teachers">المدرسون</option>
            <option value="students">الطلاب</option>
            <option value="parents">أولياء الأمور</option>
            <option value="suspended">الموقوفون</option>
        </select>
        <x-text-input wire:model.live.debounce.300ms="search" class="max-w-xs" placeholder="بحث بالاسم / الإيميل / الهاتف" />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-gray-500">
                    <th class="py-2 pe-4 font-medium">الاسم</th>
                    <th class="py-2 pe-4 font-medium">البريد</th>
                    <th class="py-2 pe-4 font-medium">الحالة</th>
                    <th class="py-2 pe-4 font-medium">الظهور العام</th>
                    <th class="py-2 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-gray-100" wire:key="mod-{{ $user->id }}">
                        <td class="py-3 pe-4">{{ $user->name }}</td>
                        <td class="py-3 pe-4">{{ $user->email }}</td>
                        <td class="py-3 pe-4">{{ $user->status->label() }}</td>
                        <td class="py-3 pe-4">
                            @if ($user->hasRole('teacher'))
                                {{ $user->is_publicly_visible ? 'ظاهر' : 'مخفي' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 space-x-2 space-x-reverse">
                            @if ($user->status === \App\Enums\UserStatus::Suspended)
                                <button type="button" wire:click="unsuspend({{ $user->id }})" class="text-brand-700 hover:underline">إعادة تفعيل</button>
                            @elseif ($user->id !== auth()->id())
                                <button type="button" wire:click="suspend({{ $user->id }})" class="text-red-600 hover:underline" wire:confirm="إيقاف هذا الحساب؟">إيقاف</button>
                            @endif
                            @if ($user->hasRole('teacher') && $user->is_publicly_visible)
                                <button type="button" wire:click="hideFromCatalog({{ $user->id }})" class="text-amber-700 hover:underline">إخفاء من الكتالوج</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-gray-500">لا توجد نتائج.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
