<div class="space-y-6">

    {{-- Flash --}}
    @if (session('status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('status') }}
        </div>
    @endif

    {{-- Public profile link --}}
    @if ($publicUrl)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold text-brand-800">صفحتك العامة</div>
                <a href="{{ $publicUrl }}" target="_blank" class="mt-0.5 block truncate text-sm text-brand-700 hover:underline">{{ $publicUrl }}</a>
            </div>
            <a href="{{ $publicUrl }}" target="_blank" class="btn-brand !px-3 !py-2 text-xs">فتح ↗</a>
        </div>
    @endif

    {{-- ════ Avatar Upload Card ════ --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-soft">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-ink">الصورة الشخصية</h3>
            <p class="mt-0.5 text-xs text-ink-muted">تظهر في صفحتك العامة وكارد المدرس.</p>
        </div>
        <div class="flex flex-wrap items-center gap-5 px-5 py-5">
            {{-- Current avatar --}}
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-brand-100 shadow-soft">
                @if ($currentAvatarUrl)
                    <img src="{{ $currentAvatarUrl }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-2xl font-bold text-brand-700">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>
                @endif
            </div>

            {{-- Upload form --}}
            <div class="flex-1 space-y-3">
                <div>
                    <input type="file" wire:model="avatarUpload" accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-ink-muted file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <p class="mt-1 text-xs text-ink-muted">JPG · PNG · WebP · حتى 4 MB</p>
                    <div wire:loading wire:target="avatarUpload" class="mt-1 text-xs text-brand-700">جاري التجهيز…</div>
                    <x-input-error :messages="$errors->get('avatarUpload')" class="mt-1" />
                </div>
                <x-primary-button type="button" wire:click="uploadAvatar" wire:loading.attr="disabled">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    رفع الصورة
                </x-primary-button>
            </div>
        </div>
    </div>

    {{-- ════ Main Profile Form ════ --}}
    <form wire:submit="save" class="space-y-5">

        {{-- Name + Phone --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="الاسم الكامل" />
                <x-text-input wire:model="name" id="name" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="phone" value="رقم الهاتف" />
                <x-text-input wire:model="phone" id="phone" class="mt-1.5 block w-full" dir="ltr" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>

        {{-- Headline --}}
        <div>
            <x-input-label for="headline" value="تخصص / عنوان قصير" />
            <x-text-input wire:model="headline" id="headline" class="mt-1.5 block w-full" placeholder="مدرس رياضيات — أولى ثانوي" />
            <x-input-error :messages="$errors->get('headline')" class="mt-1" />
        </div>

        {{-- Bio --}}
        <div>
            <x-input-label for="bio" value="نبذة عنك" />
            <textarea wire:model="bio" id="bio" rows="4" class="mt-1.5 block w-full" placeholder="اكتب نبذة تظهر لطلابك في الكتالوج…"></textarea>
            <x-input-error :messages="$errors->get('bio')" class="mt-1" />
        </div>

        {{-- Payment --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="vodafoneCashNumber" value="رقم فودافون كاش" />
                <x-text-input wire:model="vodafoneCashNumber" id="vodafoneCashNumber" class="mt-1.5 block w-full" dir="ltr" />
                <p class="mt-1 text-xs text-ink-muted">يظهر للطلاب وأولياء الأمور عند الدفع.</p>
                <x-input-error :messages="$errors->get('vodafoneCashNumber')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="paymentInstructions" value="تعليمات الدفع" />
                <textarea wire:model="paymentInstructions" id="paymentInstructions" rows="3" class="mt-1.5 block w-full" placeholder="حوّل باسم المدرس واكتب اسم الطالب في الملاحظات"></textarea>
                <x-input-error :messages="$errors->get('paymentInstructions')" class="mt-1" />
            </div>
        </div>

        {{-- ════ Subject Assignment ════ --}}
        <div class="rounded-2xl border border-brand-100 bg-brand-50/40 p-5">
            <div class="mb-4">
                <h4 class="text-sm font-bold text-brand-950">المواد الدراسية</h4>
                <p class="mt-0.5 text-xs text-ink-muted">اختر من كتالوج السنتر (متعدد) أو أضف مادة مخصصة.</p>
            </div>

            {{-- Mode toggle --}}
            <div class="mb-4 flex flex-wrap gap-4 text-sm">
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="catalog" class="text-brand-700 focus:ring-brand-500">
                    <span>اختيار من الكتالوج (متعدد)</span>
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="radio" wire:model.live="subjectMode" value="custom" class="text-brand-700 focus:ring-brand-500">
                    <span>كتابة اسم مادتي</span>
                </label>
            </div>

            @if ($subjectMode === 'catalog')
                {{-- Multi-select checkboxes grouped by stage/grade --}}
                <div class="space-y-4 rounded-2xl border border-white bg-white/80 p-4">
                    @if ($catalogSubjects->isEmpty())
                        <p class="text-sm text-ink-muted">لا توجد مواد في كتالوج السنتر بعد.</p>
                    @else
                        @foreach ($subjectsByStage as $groupLabel => $groupSubjects)
                            <div>
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-ink-muted">{{ $groupLabel }}</p>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($groupSubjects as $subject)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200/80 px-3 py-2.5 text-sm transition
                                            {{ in_array($subject->id, $selectedSubjectIds) ? 'border-brand-300 bg-brand-50' : 'hover:border-brand-200 hover:bg-slate-50' }}">
                                            <input type="checkbox"
                                                wire:model="selectedSubjectIds"
                                                value="{{ $subject->id }}"
                                                class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                                            <span class="font-medium text-ink">{{ $subject->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <x-input-error :messages="$errors->get('selectedSubjectIds')" class="mt-2" />

            @else
                {{-- Single custom subject --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="الصف الدراسي" />
                        <select wire:model="gradeId" class="mt-1.5 block w-full">
                            <option value="">اختر الصف</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="اسم المادة" />
                        <x-text-input wire:model="subjectName" class="mt-1.5 block w-full" placeholder="مثال: رياضيات متقدمة" />
                        <x-input-error :messages="$errors->get('subjectName')" class="mt-1" />
                    </div>
                </div>
            @endif
        </div>

        {{-- Public visibility --}}
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 px-4 py-3.5">
            <input type="checkbox" wire:model="isPubliclyVisible"
                class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
            <span>
                <span class="block text-sm font-semibold text-ink">أظهر صفحتي في كتالوج المدرسين</span>
                <span class="mt-0.5 block text-xs text-ink-muted">يلزم: نبذة أو عنوان + رقم فودافون + مادة واحدة على الأقل</span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('isPubliclyVisible')" />

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <p class="text-xs text-ink-muted">آخر تحديث: {{ auth()->user()->updated_at->diffForHumans() }}</p>
            <x-primary-button type="submit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                حفظ البروفايل
            </x-primary-button>
        </div>
    </form>
</div>
