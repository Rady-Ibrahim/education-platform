<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <x-input-label for="studentCode" value="كود الطالب" />
            <x-text-input wire:model="studentCode" id="studentCode" class="block mt-1 w-full" placeholder="STU-26-XXXXX" />
            <x-input-error :messages="$errors->get('studentCode')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="relationship" value="صلة القرابة" />
            <select wire:model="relationship" id="relationship" class="block mt-1 w-full border-gray-300 rounded-md">
                <option value="">اختياري</option>
                <option value="father">أب</option>
                <option value="mother">أم</option>
                <option value="guardian">وصي</option>
            </select>
        </div>
        <div>
            <x-input-label for="message" value="ملاحظة (اختياري)" />
            <x-text-input wire:model="message" id="message" class="block mt-1 w-full" />
        </div>
        <x-primary-button type="submit">ربط الابن الآن</x-primary-button>
    </form>
</div>
