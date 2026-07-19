<div class="space-y-5">
    @if (session('enroll_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('enroll_status') }}
        </div>
    @endif

    <x-page-section title="تسجيل الطالب في صف" subtitle="اربط طالبًا بصف دراسي.">
        <form wire:submit="enroll" class="mb-4 grid grid-cols-1 items-end gap-3 md:grid-cols-3">
            <div>
                <x-input-label value="الطالب" />
                <select wire:model="studentId" class="mt-1.5 block w-full">
                    <option value="">—</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('studentId')" />
            </div>
            <div>
                <x-input-label value="الصف" />
                <select wire:model="gradeId" class="mt-1.5 block w-full">
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
                <div class="list-row text-sm">
                    <span>
                        {{ $student->name }}
                        →
                        {{ $student->grades->map(fn ($g) => ($g->stage?->name.' / '.$g->name))->join('، ') }}
                    </span>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا يوجد طلاب مسجلين في صفوف بعد.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>
</div>
