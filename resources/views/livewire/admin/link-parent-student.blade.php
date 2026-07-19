<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <x-page-section title="ربط ولي أمر بطالب" subtitle="ربط مباشر نشط عند الحاجة.">
        <form wire:submit="submit" class="grid gap-3 md:grid-cols-3">
            <div>
                <x-input-label value="إيميل ولي الأمر" />
                <x-text-input wire:model="parentEmail" type="email" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('parentEmail')" />
            </div>
            <div>
                <x-input-label value="كود الطالب" />
                <x-text-input wire:model="studentCode" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('studentCode')" />
            </div>
            <div>
                <x-input-label value="صلة القرابة" />
                <select wire:model="relationship" class="mt-1.5 block w-full">
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
    </x-page-section>
</div>
