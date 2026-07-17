<div class="space-y-4">
    @if (session('enroll_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('enroll_status') }}
        </div>
    @endif

    <form wire:submit="enroll" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
        <div>
            <x-input-label value="الطالب" />
            <select wire:model="studentId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">—</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('studentId')" />
        </div>
        <div>
            <x-input-label value="الصف" />
            <select wire:model="gradeId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">—</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}">{{ $grade->stage?->name }} / {{ $grade->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gradeId')" />
        </div>
        <x-primary-button>تسجيل</x-primary-button>
    </form>

    <div class="space-y-2">
        @forelse ($enrollments as $student)
            <div class="border rounded-md px-3 py-2 text-sm">
                {{ $student->name }}
                →
                {{ $student->grades->map(fn ($g) => ($g->stage?->name.' / '.$g->name))->join('، ') }}
            </div>
        @empty
            <p class="text-sm text-gray-500">لا يوجد طلاب مسجلين في صفوف بعد.</p>
        @endforelse
    </div>
</div>
