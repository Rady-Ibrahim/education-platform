<div class="space-y-5">
    @if (session('lesson_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('lesson_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <x-page-section title="المادة غير محددة" subtitle="حدّد مادتك من البروفايل قبل إضافة الدروس.">
            <p class="text-sm text-ink-muted">
                حدّد مادتك أولًا من
                <a href="{{ route('profile') }}" class="link-brand">البروفايل ↗</a>
                (اختيار من الكتالوج أو كتابة اسم المادة)، بعدين ارجع هنا لإضافة الدروس.
            </p>
        </x-page-section>
    @else
        @if ($subjects->count() === 1)
            <x-page-section title="مادتك" subtitle="كل الدروس مربوطة بهذه المادة.">
                <p class="text-sm text-ink-muted">
                    <span class="font-semibold text-ink">
                        {{ $subjects->first()->grade?->stage?->name }} / {{ $subjects->first()->grade?->name }} / {{ $subjects->first()->name }}
                    </span>
                    —
                    <a href="{{ route('profile') }}" class="link-brand">تعديل من البروفايل ↗</a>
                </p>
            </x-page-section>
        @endif

        <x-page-section title="إضافة درس" subtitle="نص · فيديو · لايف — ثم انشر لطلابك.">
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                <div>
                    <x-input-label value="الوحدة" />
                    <select wire:model="unitId" class="mt-1.5 block w-full">
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unitId')" />
                </div>
            </div>

            <div>
                <x-input-label value="عنوان الدرس" />
                <x-text-input wire:model="title" class="mt-1.5 block w-full" />
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label value="النوع" />
                    <select wire:model.live="type" class="mt-1.5 block w-full">
                        @foreach ($types as $lessonType)
                            <option value="{{ $lessonType->value }}">{{ $lessonType->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($type === 'live')
                <div class="space-y-3 rounded-2xl border border-brand-200 bg-brand-50/40 p-4">
                    <p class="text-sm text-brand-900">سجّل حصة لايف برابط زوم أو جوجل ميت أو أي رابط اجتماع.</p>
                    <div>
                        <x-input-label value="رابط الحصة" />
                        <x-text-input wire:model="meetingUrl" class="mt-1.5 block w-full" placeholder="https://zoom.us/j/..." />
                        <x-input-error :messages="$errors->get('meetingUrl')" class="mt-1" />
                        <x-input-error :messages="$errors->get('meeting_url')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="موعد الحصة (اختياري)" />
                        <x-text-input wire:model="scheduledAt" type="datetime-local" class="mt-1.5 block w-full" />
                        <x-input-error :messages="$errors->get('scheduledAt')" class="mt-1" />
                    </div>
                </div>
            @endif

            @if (in_array($type, ['video', 'mixed'], true))
                <div class="space-y-4 rounded-2xl border border-brand-200 bg-white p-4 sm:p-5">
                    <div class="flex flex-wrap gap-2 text-sm">
                        <label @class([
                            'inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 transition',
                            'border-brand-600 bg-brand-50 text-brand-900' => $videoSource === 'upload',
                            'border-slate-200 text-ink-muted hover:bg-slate-50' => $videoSource !== 'upload',
                        ])>
                            <input type="radio" wire:model.live="videoSource" value="upload" class="text-brand-700">
                            رفع فيديو
                        </label>
                        <label @class([
                            'inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 transition',
                            'border-brand-600 bg-brand-50 text-brand-900' => $videoSource === 'record',
                            'border-slate-200 text-ink-muted hover:bg-slate-50' => $videoSource !== 'record',
                        ])>
                            <input type="radio" wire:model.live="videoSource" value="record" class="text-brand-700">
                            تسجيل الحصة
                        </label>
                        <label @class([
                            'inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 transition',
                            'border-brand-600 bg-brand-50 text-brand-900' => $videoSource === 'manual',
                            'border-slate-200 text-ink-muted hover:bg-slate-50' => $videoSource !== 'manual',
                        ])>
                            <input type="radio" wire:model.live="videoSource" value="manual" class="text-brand-700">
                            لصق Bunny ID
                        </label>
                    </div>

                    @if (! $canUploadVideo)
                        <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                            <p class="font-semibold">الرفع والتسجيل غير مفعّلين حاليًا</p>
                            <p class="text-amber-900/90">
                                أضف مفاتيح Bunny في ملف <code class="rounded bg-amber-100 px-1">.env</code> ثم نفّذ
                                <code class="rounded bg-amber-100 px-1">php artisan config:clear</code>:
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-xs text-amber-900/90 sm:text-sm">
                                <li><code>BUNNY_LIBRARY_ID</code> — رقم مكتبة Stream</li>
                                <li><code>BUNNY_TOKEN_AUTH_KEY</code> — مفتاح Token Authentication</li>
                                <li><code>BUNNY_STREAM_API_KEY</code> — مفتاح API للرفع</li>
                                <li><code>BUNNY_CDN_HOSTNAME</code> — (اختياري) لاستضافة التشغيل</li>
                            </ul>
                            <p class="text-xs text-amber-800">بديل مؤقت: اختر «لصق Bunny ID» بعد رفع الفيديو من لوحة Bunny مباشرة.</p>
                        </div>
                    @endif

                    @if ($videoSource === 'upload')
                        <div>
                            <x-input-label value="ملف الفيديو" />
                            <input type="file" wire:model="videoUpload" accept="video/*" class="mt-1.5 block w-full text-sm">
                            <p class="mt-1 text-xs text-ink-muted">الحد الأقصى {{ $maxUploadMb }} ميجابايت — يُرفع مباشرة إلى Bunny Stream.</p>
                            <div wire:loading wire:target="videoUpload" class="mt-1 text-sm text-brand-700">جاري تجهيز الملف…</div>
                            <x-input-error :messages="$errors->get('videoUpload')" class="mt-1" />
                        </div>
                    @elseif ($videoSource === 'record')
                        <div x-data="lessonRecorder()" class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-ink">استوديو التسجيل</h4>
                                    <p class="mt-0.5 text-sm text-ink-muted">سجّل الشاشة أو الكاميرا من المتصفح، ثم ارفع لـ Bunny.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="startCamera()" class="btn-brand !px-3 !py-2 text-xs sm:text-sm" :disabled="recording">كاميرا</button>
                                    <button type="button" @click="startScreen()" class="btn-brand !px-3 !py-2 text-xs sm:text-sm" :disabled="recording">شاشة + صوت</button>
                                    <button type="button" @click="stop()" class="inline-flex items-center rounded-xl bg-rose-600 px-3 py-2 text-xs font-bold text-white sm:text-sm" x-show="recording" x-cloak>إيقاف ورفع</button>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-panel">
                                <div class="flex items-center justify-between border-b border-white/10 px-4 py-2 text-xs text-white/70">
                                    <span x-show="recording" x-cloak class="inline-flex items-center gap-2 font-semibold text-rose-300">
                                        <span class="h-2 w-2 animate-pulse rounded-full bg-rose-500"></span>
                                        جاري التسجيل
                                    </span>
                                    <span x-show="! recording">معاينة</span>
                                    <span x-text="status" class="truncate text-white/50"></span>
                                </div>
                                <video
                                    x-ref="preview"
                                    class="aspect-video w-full min-h-[280px] bg-black object-contain sm:min-h-[420px] lg:min-h-[520px]"
                                    muted
                                    playsinline
                                ></video>
                            </div>

                            <x-input-error :messages="$errors->get('videoUpload')" class="mt-1" />
                        </div>
                    @else
                        <div>
                            <x-input-label value="Bunny Video ID" />
                            <x-text-input wire:model="bunnyVideoId" class="mt-1.5 block w-full" dir="ltr" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                            <x-input-error :messages="$errors->get('bunnyVideoId')" />
                            <x-input-error :messages="$errors->get('bunny_video_id')" />
                        </div>
                    @endif

                    @if ($bunnyVideoId !== '')
                        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                            فيديو مرتبط:
                            <span class="font-mono" dir="ltr">{{ $bunnyVideoId }}</span>
                        </p>
                    @endif
                    @if ($uploadStatus !== '')
                        <p class="text-sm text-brand-800">{{ $uploadStatus }}</p>
                    @endif
                </div>
            @endif

            <div>
                <x-input-label value="المحتوى النصي" />
                <textarea wire:model="body" rows="4" class="mt-1.5 block w-full"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="isPublished" class="rounded border-gray-300 text-brand-700 shadow-sm">
                نشر مباشرة
            </label>

            <div>
                <x-primary-button>حفظ الدرس</x-primary-button>
            </div>
        </form>
        </x-page-section>

        <x-page-section title="دروس الوحدة المختارة" subtitle="نشر، مرفق، أو حذف لكل درس.">
            <div class="space-y-3">
            @forelse ($lessons as $lesson)
                <div class="list-row !items-stretch !flex-col space-y-3">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm">
                            <span class="font-bold text-ink">{{ $lesson->title }}</span>
                            <span class="text-ink-muted">— {{ $lesson->type->label() }}</span>
                            @if ($lesson->is_published)
                                <span class="ms-1 text-emerald-600">منشور</span>
                            @else
                                <span class="ms-1 text-amber-600">مسودة</span>
                            @endif
                            @if ($lesson->attachments->isNotEmpty())
                                <span class="text-xs text-ink-muted">({{ $lesson->attachments->count() }} مرفق)</span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <x-secondary-button wire:click="togglePublish({{ $lesson->id }})">
                                {{ $lesson->is_published ? 'إلغاء النشر' : 'نشر' }}
                            </x-secondary-button>
                            <x-danger-button wire:click="deleteLesson({{ $lesson->id }})">حذف</x-danger-button>
                        </div>
                    </div>

                    @if ($lesson->attachments->isNotEmpty())
                        <ul class="space-y-1 text-xs text-ink-muted">
                            @foreach ($lesson->attachments as $attachment)
                                <li>{{ $attachment->name }}@if($attachment->is_downloadable) — قابل للتحميل @endif</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="mb-2 text-xs font-semibold text-ink-muted">رفع مرفق (PDF / ملف)</div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <input
                                    type="file"
                                    wire:model="attachmentUpload"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg"
                                    class="block w-full text-sm"
                                >
                                <x-input-error :messages="$errors->get('attachmentUpload')" class="mt-1" />
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs text-ink-muted">
                                <input type="checkbox" wire:model="attachmentDownloadable" class="rounded border-slate-300 text-brand-700">
                                قابل للتحميل
                            </label>
                            <x-secondary-button type="button" wire:click="uploadAttachmentFor({{ $lesson->id }})">رفع</x-secondary-button>
                        </div>
                        <div wire:loading wire:target="attachmentUpload" class="mt-1 text-xs text-brand-700">جاري تجهيز الملف…</div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <p class="text-sm text-ink-muted">لا توجد دروس في هذه الوحدة بعد.</p>
                </div>
            @endforelse
            </div>
        </x-page-section>
    @endif
</div>

@script
<script>
    Alpine.data('lessonRecorder', () => ({
        recording: false,
        status: '',
        mediaRecorder: null,
        chunks: [],
        stream: null,

        async startCamera() {
            await this.start(await navigator.mediaDevices.getUserMedia({ video: true, audio: true }));
        },

        async startScreen() {
            const display = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
            let stream = display;
            try {
                const mic = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream = new MediaStream([
                    ...display.getVideoTracks(),
                    ...mic.getAudioTracks(),
                    ...display.getAudioTracks(),
                ]);
            } catch (e) {
                // screen audio only
            }
            await this.start(stream);
        },

        async start(stream) {
            this.stream = stream;
            this.$refs.preview.srcObject = stream;
            await this.$refs.preview.play();
            this.chunks = [];
            const mime = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
                ? 'video/webm;codecs=vp9,opus'
                : 'video/webm';
            this.mediaRecorder = new MediaRecorder(stream, { mimeType: mime });
            this.mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.chunks.push(e.data);
            };
            this.mediaRecorder.onstop = () => this.uploadRecording();
            this.mediaRecorder.start(1000);
            this.recording = true;
            this.status = 'جاري التسجيل…';
        },

        stop() {
            if (! this.mediaRecorder || this.mediaRecorder.state === 'inactive') return;
            this.mediaRecorder.stop();
            this.recording = false;
            this.status = 'جاري تجهيز الملف للرفع…';
            this.stream?.getTracks()?.forEach((t) => t.stop());
            this.$refs.preview.srcObject = null;
        },

        uploadRecording() {
            const blob = new Blob(this.chunks, { type: 'video/webm' });
            const file = new File([blob], 'lesson-recording-' + Date.now() + '.webm', { type: 'video/webm' });
            this.status = 'جاري رفع التسجيل…';
            $wire.upload('videoUpload', file, () => {
                $wire.processRecordedUpload().then(() => {
                    this.status = 'اكتمل الرفع.';
                }).catch(() => {
                    this.status = 'فشل الرفع.';
                });
            }, () => {
                this.status = 'فشل تجهيز الملف.';
            }, (event) => {
                if (event.detail?.progress) {
                    this.status = 'رفع… ' + Math.round(event.detail.progress * 100) + '%';
                }
            });
        },
    }));
</script>
@endscript
