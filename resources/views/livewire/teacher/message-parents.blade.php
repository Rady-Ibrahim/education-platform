<div class="space-y-8">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="send" class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label value="الطالب (اختياري)" />
                <select wire:model.live="studentId" class="mt-1.5 block w-full">
                    <option value="">كل الأبناء المرتبطين</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="ولي الأمر" />
                <select wire:model="parentId" class="mt-1.5 block w-full">
                    <option value="">اختر ولي الأمر</option>
                    @foreach ($parentLinks as $link)
                        <option value="{{ $link->parent_id }}">
                            {{ $link->parent?->name }} — ابن: {{ $link->student?->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('parentId')" />
            </div>
        </div>

        <div>
            <x-input-label value="الرسالة" />
            <textarea wire:model="body" rows="4" class="mt-1.5 block w-full" placeholder="مثال: ابنك محتاج يراجع الوحدة الأولى قبل الامتحان…"></textarea>
            <x-input-error :messages="$errors->get('body')" />
        </div>

        <div>
            <x-input-label value="صورة (اختياري)" />
            <input type="file" wire:model="image" accept="image/*" class="mt-1.5 block w-full text-sm">
            <div wire:loading wire:target="image" class="mt-1 text-xs text-brand-700">جاري تجهيز الصورة…</div>
            <x-input-error :messages="$errors->get('image')" />
        </div>

        <x-primary-button type="submit">إرسال لولي الأمر</x-primary-button>
    </form>

    <section>
        <h3 class="mb-3 text-sm font-bold text-ink">آخر الرسائل المرسلة</h3>
        <div class="space-y-3">
            @forelse ($sent as $message)
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-ink">إلى: {{ $message->parent?->name }}</div>
                            @if ($message->student)
                                <div class="text-xs text-ink-muted">بخصوص: {{ $message->student->name }}</div>
                            @endif
                        </div>
                        <div class="text-xs text-ink-muted">{{ $message->created_at?->diffForHumans() }}</div>
                    </div>
                    <p class="mt-2 text-sm text-ink-soft whitespace-pre-line">{{ $message->body }}</p>
                    @if ($message->imageUrl())
                        <a href="{{ $message->imageUrl() }}" target="_blank" class="mt-2 inline-flex text-sm font-semibold text-brand-700">عرض الصورة</a>
                    @endif
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-ink-muted">
                    لم ترسل رسائل بعد.
                </p>
            @endforelse
        </div>
    </section>
</div>
