<div class="space-y-5">

    {{-- Flash --}}
    @if (session('lesson_status'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('lesson_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <x-page-section title="المادة غير محددة">
            <p class="text-sm text-ink-muted">
                حدّد مادتك أولًا من
                <a href="{{ route('profile') }}" wire:navigate class="link-brand font-semibold">البروفايل ↗</a>
                ثم ارجع هنا لإضافة الدروس.
            </p>
        </x-page-section>
    @else

        {{-- ── Subject + Unit Picker ── --}}
        <x-page-section>
            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Subject --}}
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model.live="subjectId" class="mt-1.5 block w-full">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Unit --}}
                <div>
                    <x-input-label value="الوحدة الدراسية" />
                    @if ($units->isEmpty())
                        <div class="mt-1.5 flex items-center gap-3 rounded-xl border border-dashed border-brand-200 bg-brand-50/50 px-4 py-2.5">
                            <p class="flex-1 text-sm text-ink-muted">لا توجد وحدات بعد.</p>
                            <button type="button" wire:click="openCreateUnit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-brand-800">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                إضافة وحدة
                            </button>
                        </div>
                    @else
                        <div class="mt-1.5 flex gap-2">
                            <select wire:model="unitId" class="flex-1 block w-full">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="openCreateUnit"
                                title="وحدة جديدة"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2.5 text-ink-muted transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('unitId')" />
                </div>
            </div>

            {{-- Create Unit Inline Form --}}
            @if ($showCreateUnit)
                <div class="mt-4 rounded-2xl border border-brand-200 bg-brand-50/40 p-4" x-data x-init="$el.querySelector('input')?.focus()">
                    <h4 class="mb-3 text-sm font-bold text-brand-950">وحدة جديدة</h4>
                    <div class="flex gap-3">
                        <x-text-input wire:model="newUnitName" wire:keydown.enter="saveUnit"
                            placeholder="اسم الوحدة — مثلاً: الفصل الأول" class="flex-1" />
                        <x-primary-button type="button" wire:click="saveUnit">إنشاء</x-primary-button>
                        <x-secondary-button type="button" wire:click="cancelCreateUnit">إلغاء</x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('newUnitName')" class="mt-1.5" />
                </div>
            @endif
        </x-page-section>

        {{-- ── Create Lesson Form ── --}}
        <x-page-section title="إضافة درس" subtitle="نص · فيديو · لايف — ثم انشر لطلابك.">
            <form wire:submit="save" class="space-y-5">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="عنوان الدرس" />
                        <x-text-input wire:model="title" class="mt-1.5 block w-full" placeholder="مثلاً: مقدمة في الجبر" />
                        <x-input-error :messages="$errors->get('title')" />
                    </div>
                    <div>
                        <x-input-label value="نوع الدرس" />
                        <select wire:model.live="type" class="mt-1.5 block w-full">
                            @foreach ($types as $lessonType)
                                <option value="{{ $lessonType->value }}">{{ $lessonType->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Live fields --}}
                @if ($type === 'live')
                    <div class="space-y-4 rounded-2xl border border-[#1A73E8]/30 bg-[#F8FBFF] p-5">
                        {{-- Google Meet header --}}
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-soft ring-1 ring-[#1A73E8]/20">
                                {{-- Google Meet icon (SVG) --}}
                                <svg viewBox="0 0 87.5 75" class="h-5 w-5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M50 31.25v12.5H37.5v-12.5H50z" fill="#00832D"/>
                                    <path d="M50 43.75v12.5l12.5-6.25V37.5L50 43.75z" fill="#0066DA"/>
                                    <path d="M37.5 43.75H25v12.5h12.5V43.75z" fill="#E94235"/>
                                    <path d="M37.5 31.25H25v12.5h12.5v-12.5z" fill="#2684FC"/>
                                    <path d="M50 31.25l12.5-6.25v12.5L50 43.75V31.25z" fill="#00AC47"/>
                                    <path d="M37.5 56.25v-12.5L25 56.25h12.5z" fill="#FF6D00"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-ink">حصة مباشرة عبر Google Meet</p>
                                <p class="text-xs text-ink-muted">أدخل رابط الاجتماع وموعد الحصة ليظهر للطلاب</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-input-label value="رابط Google Meet *" />
                                <div class="relative mt-1.5">
                                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                                        <svg class="h-4 w-4 text-[#1A73E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </div>
                                    <x-text-input
                                        wire:model="meetingUrl"
                                        class="block w-full pe-10"
                                        dir="ltr"
                                        placeholder="https://meet.google.com/xxx-xxxx-xxx"
                                    />
                                </div>
                                <p class="mt-1 text-xs text-ink-muted">مثال: https://meet.google.com/abc-defg-hij</p>
                                <x-input-error :messages="$errors->get('meetingUrl')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label value="موعد الحصة" />
                                <x-text-input wire:model="scheduledAt" type="datetime-local" class="mt-1.5 block w-full" />
                                <p class="mt-1 text-xs text-ink-muted">يظهر للطلاب مع عداد تنازلي</p>
                                <x-input-error :messages="$errors->get('scheduledAt')" class="mt-1" />
                            </div>

                            <div class="flex items-end">
                                @if ($meetingUrl !== '')
                                    <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 rounded-xl border border-[#1A73E8]/30 bg-white px-4 py-2.5 text-sm font-semibold text-[#1A73E8] shadow-soft transition hover:bg-[#E8F0FE]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        اختبار الرابط
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Video fields --}}
                @if (in_array($type, ['video', 'mixed'], true))
                    <div class="space-y-4 rounded-2xl border border-brand-200 bg-white p-4 sm:p-5">
                        <div class="flex flex-wrap gap-2 text-sm">
                            @foreach ([['upload','رفع فيديو'],['record','تسجيل الحصة'],['manual','لصق Bunny ID']] as [$val,$label])
                                <label @class([
                                    'inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 transition',
                                    'border-brand-600 bg-brand-50 text-brand-900' => $videoSource === $val,
                                    'border-slate-200 text-ink-muted hover:bg-slate-50' => $videoSource !== $val,
                                ])>
                                    <input type="radio" wire:model.live="videoSource" value="{{ $val }}" class="text-brand-700">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        @if (! $canUploadVideo)
                            {{-- Bunny not configured — clear setup guide --}}
                            <div class="space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-start gap-2.5">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    <div>
                                        <p class="text-sm font-bold text-amber-900">رفع الفيديو غير مفعّل</p>
                                        <p class="mt-1 text-xs text-amber-800">أضف مفاتيح Bunny Stream في ملف <code class="rounded bg-amber-100 px-1 font-mono">.env</code> ثم نفّذ <code class="rounded bg-amber-100 px-1 font-mono">php artisan config:clear</code></p>
                                    </div>
                                </div>
                                <div class="grid gap-1.5 rounded-xl bg-amber-100/60 p-3 font-mono text-xs text-amber-900">
                                    <div><span class="select-all">BUNNY_LIBRARY_ID</span>=<span class="text-amber-600">رقم مكتبة Stream</span></div>
                                    <div><span class="select-all">BUNNY_TOKEN_AUTH_KEY</span>=<span class="text-amber-600">مفتاح Token Auth</span></div>
                                    <div><span class="select-all">BUNNY_STREAM_API_KEY</span>=<span class="text-amber-600">مفتاح API للرفع</span></div>
                                </div>
                                <p class="text-xs text-amber-800">بديل مؤقت: ارفع الفيديو مباشرة من لوحة Bunny ثم اختر «لصق Bunny ID» واصق المعرّف.</p>
                            </div>
                        @endif

                        @if ($videoSource === 'upload')
                            <div>
                                <x-input-label value="ملف الفيديو" />
                                <input type="file" wire:model="videoUpload" accept="video/*"
                                    class="mt-1.5 block w-full text-sm text-ink-muted
                                           file:me-3 file:rounded-lg file:border-0 file:bg-brand-50
                                           file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700
                                           hover:file:bg-brand-100">
                                <p class="mt-1 text-xs text-ink-muted">MP4 · WebM · MOV — الحد الأقصى {{ $maxUploadMb }} MB</p>

                                {{-- Upload progress --}}
                                <div wire:loading wire:target="videoUpload" class="mt-3">
                                    <div class="flex items-center justify-between text-xs text-brand-700 mb-1">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                            جاري تجهيز الملف…
                                        </span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-brand-100">
                                        <div class="h-full w-1/2 animate-pulse rounded-full bg-brand-500"></div>
                                    </div>
                                </div>

                                {{-- Upload status after Bunny upload --}}
                                @if ($uploadStatus !== '')
                                    <div @class([
                                        'mt-3 flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm',
                                        'border border-emerald-200 bg-emerald-50 text-emerald-800' => str_contains($uploadStatus, '✓'),
                                        'border border-brand-200 bg-brand-50 text-brand-800' => ! str_contains($uploadStatus, '✓'),
                                    ])>
                                        @if (str_contains($uploadStatus, '✓'))
                                            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                        @endif
                                        {{ $uploadStatus }}
                                    </div>
                                @endif

                                @if ($uploadProgress > 0 && $uploadProgress < 100)
                                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-brand-100">
                                        <div class="h-full rounded-full bg-brand-600 transition-all duration-300"
                                             style="width: {{ $uploadProgress }}%"></div>
                                    </div>
                                @endif

                                <x-input-error :messages="$errors->get('videoUpload')" class="mt-1.5" />
                            </div>
                        @elseif ($videoSource === 'record')
                            <div x-data="lessonRecorder()" class="space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-ink">استوديو التسجيل</p>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="startCamera()" class="btn-brand !px-3 !py-2 text-xs" :disabled="recording">كاميرا</button>
                                        <button type="button" @click="startScreen()" class="btn-brand !px-3 !py-2 text-xs" :disabled="recording">شاشة + صوت</button>
                                        <button type="button" @click="stop()" class="inline-flex items-center rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white" x-show="recording" x-cloak>إيقاف ورفع</button>
                                    </div>
                                </div>
                                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
                                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-2 text-xs text-white/70">
                                        <span x-show="recording" x-cloak class="inline-flex items-center gap-2 font-semibold text-rose-300">
                                            <span class="h-2 w-2 animate-pulse rounded-full bg-rose-500"></span>
                                            جاري التسجيل
                                        </span>
                                        <span x-show="!recording">معاينة</span>
                                        <span x-text="status" class="truncate text-white/50"></span>
                                    </div>
                                    <video x-ref="preview" class="aspect-video w-full bg-black object-contain min-h-[280px] sm:min-h-[360px]" muted playsinline></video>
                                </div>
                                <x-input-error :messages="$errors->get('videoUpload')" class="mt-1" />
                            </div>
                        @else
                            <div>
                                <x-input-label value="Bunny Video ID" />
                                <x-text-input wire:model="bunnyVideoId" class="mt-1.5 block w-full" dir="ltr" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                                <x-input-error :messages="$errors->get('bunnyVideoId')" />
                            </div>
                        @endif

                        @if ($bunnyVideoId !== '')
                            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                                فيديو مرتبط: <span class="font-mono" dir="ltr">{{ $bunnyVideoId }}</span>
                            </p>
                        @endif
                        @if ($uploadStatus !== '')
                            <p class="text-sm text-brand-800">{{ $uploadStatus }}</p>
                        @endif
                    </div>
                @endif

                {{-- Body --}}
                <div>
                    <x-input-label value="المحتوى النصي (اختياري)" />
                    <textarea wire:model="body" rows="4" class="mt-1.5 block w-full"></textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="isPublished" class="rounded border-gray-300 text-brand-700">
                        <span>نشر مباشرة</span>
                    </label>
                    <x-primary-button>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        حفظ الدرس
                    </x-primary-button>
                </div>
            </form>
        </x-page-section>

        {{-- ── Lessons List ── --}}
        <x-page-section title="دروس الوحدة" subtitle="تعديل، نشر، مرفقات، أو حذف.">
            @php
                $publishedCount = $lessons->where('is_published', true)->count();
                $draftCount     = $lessons->where('is_published', false)->count();
            @endphp

            @if ($lessons->isNotEmpty())
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $publishedCount }} منشور
                    </span>
                    @if ($draftCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-amber-100">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ $draftCount }} مسودة — غير مرئية للطلاب
                        </span>
                    @endif
                    <span class="text-xs text-ink-muted">{{ $lessons->count() }} درس إجمالاً</span>
                </div>
            @endif

            <div class="space-y-3">
            @forelse ($lessons as $lesson)
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-soft transition duration-200 hover:border-brand-200"
                     wire:key="lesson-{{ $lesson->id }}">

                    {{-- Lesson header row --}}
                    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-ink">{{ $lesson->title }}</span>
                                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-ink-muted">{{ $lesson->type->label() }}</span>
                                @if ($lesson->is_published)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        منشور — يراه الطلاب
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-100">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        مسودة — غير مرئي للطلاب
                                    </span>
                                @endif
                                @if ($lesson->attachments->isNotEmpty())
                                    <span class="rounded-lg bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-100">
                                        📎 {{ $lesson->attachments->count() }}
                                    </span>
                                @endif
                                @if ($lesson->hasVideo())
                                    <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-ink-muted">🎬 فيديو</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button" wire:click="openEditLesson({{ $lesson->id }})"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                تعديل
                            </button>
                            <button type="button" wire:click="openAttachPanel({{ $lesson->id }})"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                مرفق
                            </button>
                            <button type="button" wire:click="togglePublish({{ $lesson->id }})"
                                @class([
                                    'inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition',
                                    'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => ! $lesson->is_published,
                                    'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'   => $lesson->is_published,
                                ])>
                                {{ $lesson->is_published ? 'إلغاء النشر' : '⬆ نشر للطلاب' }}
                            </button>
                            <button type="button"
                                wire:click="deleteLesson({{ $lesson->id }})"
                                wire:confirm="حذف الدرس '{{ $lesson->title }}'؟ لا يمكن التراجع."
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 transition hover:border-rose-200 hover:bg-rose-50">
                                حذف
                            </button>
                        </div>
                    </div>

                    {{-- Attachments list --}}
                    @if ($lesson->attachments->isNotEmpty())
                        <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                            <ul class="space-y-1.5">
                                @foreach ($lesson->attachments as $att)
                                    <li class="flex items-center justify-between gap-3 text-xs">
                                        <div class="flex min-w-0 items-center gap-2 text-ink-muted">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="truncate">{{ $att->name }}</span>
                                            @if ($att->is_downloadable)
                                                <span class="shrink-0 text-emerald-600">قابل للتحميل</span>
                                            @endif
                                        </div>
                                        <button type="button"
                                            wire:click="deleteAttachment({{ $att->id }})"
                                            wire:confirm="حذف المرفق '{{ $att->name }}'؟"
                                            class="shrink-0 rounded-lg px-2 py-0.5 text-rose-500 transition hover:bg-rose-50">حذف</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Per-lesson attach panel --}}
                    @if ($attachOpenForLesson === $lesson->id)
                        <div class="border-t border-brand-100 bg-brand-50/40 px-4 py-4">
                            <h4 class="mb-3 text-xs font-bold text-brand-900">رفع مرفق للدرس</h4>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="flex-1">
                                    <input type="file" wire:model="attachmentUpload"
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg"
                                        class="block w-full text-sm">
                                    <p class="mt-1 text-xs text-ink-muted">PDF · Word · PowerPoint · صورة (حتى 20 MB)</p>
                                    <div wire:loading wire:target="attachmentUpload" class="mt-1 text-xs text-brand-700">جاري تجهيز الملف…</div>
                                    <x-input-error :messages="$errors->get('attachmentUpload')" class="mt-1" />
                                </div>
                                <label class="inline-flex items-center gap-2 text-xs text-ink-muted whitespace-nowrap">
                                    <input type="checkbox" wire:model="attachmentDownloadable" class="rounded border-slate-300 text-brand-700">
                                    قابل للتحميل
                                </label>
                                <x-primary-button type="button" wire:click="uploadAttachment">رفع</x-primary-button>
                                <x-secondary-button type="button" wire:click="openAttachPanel({{ $lesson->id }})">إغلاق</x-secondary-button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state py-12">
                    <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <p class="text-sm text-ink-muted">لا توجد دروس في هذه الوحدة بعد.</p>
                </div>
            @endforelse
            </div>
        </x-page-section>
    @endif

    {{-- ════════════════ Edit Lesson Modal ════════════════ --}}
    @if ($showEditLesson)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-init="$el.querySelector('input,textarea')?.focus()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-brand-950/50 backdrop-blur-sm"
                 wire:click="closeEditLesson"></div>

            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-panel">
                {{-- Modal header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-bold text-ink">تعديل الدرس</h2>
                    <button type="button" wire:click="closeEditLesson"
                        class="rounded-xl border border-slate-200 p-2 text-ink-muted transition hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal body --}}
                <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
                    <form wire:submit="updateLesson" class="space-y-4">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label value="عنوان الدرس" />
                                <x-text-input wire:model="editTitle" class="mt-1.5 block w-full" />
                                <x-input-error :messages="$errors->get('editTitle')" />
                            </div>
                            <div>
                                <x-input-label value="النوع" />
                                <select wire:model.live="editType" class="mt-1.5 block w-full">
                                    @foreach ($types as $lt)
                                        <option value="{{ $lt->value }}">{{ $lt->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if ($editType === 'live')
                            <div class="space-y-4 rounded-2xl border border-[#1A73E8]/30 bg-[#F8FBFF] p-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white shadow-soft ring-1 ring-[#1A73E8]/20">
                                        <svg viewBox="0 0 87.5 75" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M50 31.25v12.5H37.5v-12.5H50z" fill="#00832D"/>
                                            <path d="M50 43.75v12.5l12.5-6.25V37.5L50 43.75z" fill="#0066DA"/>
                                            <path d="M37.5 43.75H25v12.5h12.5V43.75z" fill="#E94235"/>
                                            <path d="M37.5 31.25H25v12.5h12.5v-12.5z" fill="#2684FC"/>
                                            <path d="M50 31.25l12.5-6.25v12.5L50 43.75V31.25z" fill="#00AC47"/>
                                            <path d="M37.5 56.25v-12.5L25 56.25h12.5z" fill="#FF6D00"/>
                                        </svg>
                                    </span>
                                    <p class="text-sm font-bold text-ink">حصة مباشرة عبر Google Meet</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <x-input-label value="رابط Google Meet *" />
                                        <x-text-input wire:model="editMeetingUrl" class="mt-1.5 block w-full" dir="ltr" placeholder="https://meet.google.com/xxx-xxxx-xxx" />
                                        <x-input-error :messages="$errors->get('editMeetingUrl')" />
                                    </div>
                                    <div>
                                        <x-input-label value="موعد الحصة" />
                                        <x-text-input wire:model="editScheduledAt" type="datetime-local" class="mt-1.5 block w-full" />
                                        <x-input-error :messages="$errors->get('editScheduledAt')" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (in_array($editType, ['video', 'mixed'], true))
                            <div>
                                <x-input-label value="Bunny Video ID" />
                                <x-text-input wire:model="editBunnyVideoId" class="mt-1.5 block w-full" dir="ltr" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                                <x-input-error :messages="$errors->get('editBunnyVideoId')" />
                            </div>
                        @endif

                        <div>
                            <x-input-label value="المحتوى النصي (اختياري)" />
                            <textarea wire:model="editBody" rows="5" class="mt-1.5 block w-full"></textarea>
                            <x-input-error :messages="$errors->get('editBody')" />
                        </div>

                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="editIsPublished" class="rounded border-gray-300 text-brand-700">
                            منشور
                        </label>

                        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" wire:click="closeEditLesson">إلغاء</x-secondary-button>
                            <x-primary-button>حفظ التعديلات</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>

@script
<script>
    Alpine.data('lessonRecorder', () => ({
        recording: false, status: '', mediaRecorder: null, chunks: [], stream: null,

        async startCamera() { await this.start(await navigator.mediaDevices.getUserMedia({ video: true, audio: true })); },

        async startScreen() {
            const display = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
            let stream = display;
            try {
                const mic = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream = new MediaStream([...display.getVideoTracks(), ...mic.getAudioTracks(), ...display.getAudioTracks()]);
            } catch (_) {}
            await this.start(stream);
        },

        async start(stream) {
            this.stream = stream;
            this.$refs.preview.srcObject = stream;
            await this.$refs.preview.play();
            this.chunks = [];
            const mime = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus') ? 'video/webm;codecs=vp9,opus' : 'video/webm';
            this.mediaRecorder = new MediaRecorder(stream, { mimeType: mime });
            this.mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) this.chunks.push(e.data); };
            this.mediaRecorder.onstop = () => this.uploadRecording();
            this.mediaRecorder.start(1000);
            this.recording = true;
            this.status = 'جاري التسجيل…';
        },

        stop() {
            if (!this.mediaRecorder || this.mediaRecorder.state === 'inactive') return;
            this.mediaRecorder.stop();
            this.recording = false;
            this.status = 'جاري تجهيز الملف…';
            this.stream?.getTracks()?.forEach(t => t.stop());
            this.$refs.preview.srcObject = null;
        },

        uploadRecording() {
            const blob = new Blob(this.chunks, { type: 'video/webm' });
            const file = new File([blob], 'lesson-recording-' + Date.now() + '.webm', { type: 'video/webm' });
            this.status = 'جاري رفع التسجيل…';
            $wire.upload('videoUpload', file, () => {
                $wire.processRecordedUpload().then(() => { this.status = 'اكتمل الرفع ✓'; }).catch(() => { this.status = 'فشل الرفع.'; });
            }, () => { this.status = 'فشل تجهيز الملف.'; }, (e) => { if (e.detail?.progress) this.status = 'رفع… ' + Math.round(e.detail.progress * 100) + '%'; });
        },
    }));
</script>
@endscript
