<div class="space-y-4">
    @if (session('assign_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('assign_status') }}
        </div>
    @endif

    <form wire:submit="assign" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
        <div>
            <x-input-label value="المدرس" />
            <select wire:model="teacherId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">—</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('teacherId')" />
        </div>
        <div>
            <x-input-label value="المادة" />
            <select wire:model="subjectId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                <div class="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
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
            <p class="text-sm text-gray-500">لا توجد روابط مدرس–مادة بعد.</p>
        @endforelse
    </div>
</div>
