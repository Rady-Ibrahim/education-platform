<div>
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-4">
        <div class="flex-1">
            <x-input-label value="بحث عن طالب" />
            <x-text-input wire:model.live.debounce.300ms="search" class="block mt-1 w-full" placeholder="الاسم، الإيميل، الكود، الهاتف" />
        </div>
        <div>
            <x-input-label value="تصفية" />
            <select wire:model.live="filter" class="block mt-1 border-gray-300 rounded-md">
                <option value="all">الكل</option>
                <option value="active_sub">اشتراك نشط</option>
                <option value="pending_payment">بانتظار الدفع</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-start px-4 py-3 font-medium">الطالب</th>
                    <th class="text-start px-4 py-3 font-medium">الكود</th>
                    <th class="text-start px-4 py-3 font-medium">اشتراكات نشطة</th>
                    <th class="text-start px-4 py-3 font-medium">مدفوعات معلّقة</th>
                    <th class="text-start px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $student->name }}</div>
                            <div class="text-gray-500">{{ $student->email }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono">{{ $student->student_code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ ($activeSubs[$student->id] ?? collect())->count() }}</td>
                        <td class="px-4 py-3">{{ ($pendingByStudent[$student->id] ?? collect())->count() }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('teacher.students.show', $student) }}" class="text-indigo-600 hover:text-indigo-800" wire:navigate>
                                كشف الحساب
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد طلاب مطابقون.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>
