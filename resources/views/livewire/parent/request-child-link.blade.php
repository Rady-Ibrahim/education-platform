<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="طلب ربط ابن" subtitle="خذ كود الطالب من لوحة ابنك ثم أرسل الطلب.">
        <form wire:submit="submit" class="space-y-4">
            <div>
                <x-input-label for="studentCode" value="كود الطالب" />
                <x-text-input wire:model="studentCode" id="studentCode" class="mt-1.5 block w-full" placeholder="STU-26-XXXXX" />
                <x-input-error :messages="$errors->get('studentCode')" />
            </div>
            <div>
                <x-input-label for="relationship" value="صلة القرابة" />
                <select wire:model="relationship" id="relationship" class="mt-1.5 block w-full">
                    <option value="">اختياري</option>
                    <option value="father">أب</option>
                    <option value="mother">أم</option>
                    <option value="guardian">وصي</option>
                </select>
            </div>
            <div>
                <x-input-label for="message" value="ملاحظة (اختياري)" />
                <x-text-input wire:model="message" id="message" class="mt-1.5 block w-full" />
            </div>
            <x-primary-button type="submit">ربط الابن الآن</x-primary-button>
        </form>
    </x-page-section>
</div>
