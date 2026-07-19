<div class="space-y-5">
    @if (session('assign_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('assign_status') }}
        </div>
    @endif

    <x-page-section title="ربط المدرس بالمادة" subtitle="اربط مدرسًا بمادة من الهيكل الأكاديمي.">
        <form wire:submit="assign" class="mb-4 grid grid-cols-1 items-end gap-3 md:grid-cols-3">
            <div>
                <x-input-label value="المدرس" />
                <select wire:model="teacherId" class="mt-1.5 block w-full">
                    <option value="">—</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('teacherId')" />
            </div>
            <div>
                <x-input-label value="المادة" />
                <select wire:model="subjectId" class="mt-1.5 block w-full">
                    <option value="">—</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('subjectId')" />
            </div>
            <x-primary-button>ربط</x-primary-button>
        </form>

        <div class="space-y-2">
            @forelse ($assignments as $subject)
                @foreach ($subject->teachers as $teacher)
                    <div class="list-row text-sm">
                        <span>
                            {{ $teacher->name }}
                            →
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </span>
                        <x-danger-button wire:click="detach({{ $teacher->id }}, {{ $subject->id }})" class="text-xs">
                            إلغاء
                        </x-danger-button>
                    </div>
                @endforeach
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد روابط مدرس–مادة بعد.</p>
                </div>
            @endforelse
        </div>
    </x-page-section>
</div>
