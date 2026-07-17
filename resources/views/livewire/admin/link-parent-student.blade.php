<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="submit" class="grid gap-3 md:grid-cols-3">
        <div>
            <x-input-label value="إيميل ولي الأمر" />
            <x-text-input wire:model="parentEmail" type="email" class="block mt-1 w-full" />
            <x-input-error :messages="$errors->get('parentEmail')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="كود الطالب" />
            <x-text-input wire:model="studentCode" class="block mt-1 w-full" />
            <x-input-error :messages="$errors->get('studentCode')" class="mt-1" />
        </div>
        <div>
            <x-input-label value="صلة القرابة" />
            <select wire:model="relationship" class="block mt-1 w-full border-gray-300 rounded-md">
                <option value="">اختياري</option>
                <option value="father">أب</option>
                <option value="mother">أم</option>
                <option value="guardian">وصي</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <x-primary-button type="submit">ربط مباشر</x-primary-button>
        </div>
    </form>
</div>
