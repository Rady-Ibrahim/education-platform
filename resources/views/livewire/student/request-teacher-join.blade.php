<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <x-input-label value="اختر المدرس" />
            <select wire:model="teacherId" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                <option value="">—</option>
                @foreach ($teachers as $teacher)
                    @php
                        $joined = $myTeacherIds->contains($teacher->id);
                        $pending = $pendingTeacherIds->contains($teacher->id);
                    @endphp
                    <option value="{{ $teacher->id }}" @disabled($joined || $pending)>
                        {{ $teacher->name }}
                        @if ($joined) (منضم) @elseif ($pending) (طلب معلّق) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('teacherId')" class="mt-2" />
        </div>

        <div>
            <x-input-label value="رسالة للمدرس (اختياري)" />
            <textarea wire:model="message" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="3"></textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>

        <x-primary-button>إرسال طلب الانضمام</x-primary-button>
    </form>
</div>
