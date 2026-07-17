<div class="space-y-6">
    @if (session('lesson_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('lesson_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <p class="text-sm text-gray-500">
            حدّد مادتك أولًا من
            <a href="{{ route('profile') }}" class="text-brand-700 hover:underline" wire:navigate>البروفايل</a>
            (اختيار من الكتالوج أو كتابة اسم المادة)، بعدين ارجع هنا لإضافة الدروس.
        </p>
    @else
        @if ($subjects->count() === 1)
            <p class="text-sm text-gray-600">
                مادتك:
                <span class="font-medium text-gray-900">
                    {{ $subjects->first()->grade?->stage?->name }} / {{ $subjects->first()->grade?->name }} / {{ $subjects->first()->name }}
                </span>
                —
                <a href="{{ route('profile') }}" class="text-brand-700 hover:underline" wire:navigate>تعديل من البروفايل</a>
            </p>
        @endif
        <form wire:submit="save" class="space-y-4 border rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="المادة" />
                    <select wire:model.live="subjectId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="الوحدة" />
                    <select wire:model="unitId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unitId')" />
                </div>
            </div>

            <div>
                <x-input-label value="عنوان الدرس" />
                <x-text-input wire:model="title" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('title')" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="النوع" />
                    <select wire:model.live="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                        <x-text-input wire:model="meetingUrl" class="mt-1 block w-full" placeholder="https://zoom.us/j/..." />
                        <x-input-error :messages="$errors->get('meetingUrl')" class="mt-1" />
                        <x-input-error :messages="$errors->get('meeting_url')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="موعد الحصة (اختياري)" />
                        <x-text-input wire:model="scheduledAt" type="datetime-local" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('scheduledAt')" class="mt-1" />
                    </div>
                </div>
            @endif

            @if (in_array($type, ['video', 'mixed'], true))
                <div class="space-y-4 rounded-lg border border-brand-200 bg-brand-50/40 p-4">
                    <div class="flex flex-wrap gap-3 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="videoSource" value="upload" class="text-brand-700">
                            رفع فيديو
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="videoSource" value="record" class="text-brand-700">
                            تسجيل الحصة من المنصة
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="videoSource" value="manual" class="text-brand-700">
                            لصق Bunny ID
                        </label>
                    </div>

                    @if (! $canUploadVideo)
                        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md p-2">
                            الرفع/التسجيل يحتاج <code>BUNNY_STREAM_API_KEY</code> في الإعدادات. تقدر تلصق Video ID يدويًا كحل بديل.
                        </p>
                    @endif

                    @if ($videoSource === 'upload')
                        <div>
                            <x-input-label value="ملف الفيديو" />
                            <input type="file" wire:model="videoUpload" accept="video/*" class="mt-1 block w-full text-sm">
                            <p class="mt-1 text-xs text-gray-500">الحد الأقصى {{ $maxUploadMb }} ميجابايت — يُرفع مباشرة إلى Bunny Stream.</p>
                            <div wire:loading wire:target="videoUpload" class="mt-1 text-sm text-brand-700">جاري تجهيز الملف…</div>
                            <x-input-error :messages="$errors->get('videoUpload')" class="mt-1" />
                        </div>
                    @elseif ($videoSource === 'record')
                        <div x-data="lessonRecorder()" class="space-y-3">
                            <p class="text-sm text-gray-700">سجّل الشاشة أو الكاميرا من المتصفح، ثم ارفع التسجيل لـ Bunny.</p>
                            <video x-ref="preview" class="w-full max-w-lg rounded-md bg-black aspect-video" muted playsinline></video>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="startCamera()" class="rounded-md bg-brand-700 px-3 py-1.5 text-sm text-white" :disabled="recording">كاميرا</button>
                                <button type="button" @click="startScreen()" class="rounded-md bg-brand-700 px-3 py-1.5 text-sm text-white" :disabled="recording">شاشة + صوت</button>
                                <button type="button" @click="stop()" class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white" x-show="recording" x-cloak>إيقاف ورفع</button>
                            </div>
                            <p class="text-xs text-gray-500" x-text="status"></p>
                            <x-input-error :messages="$errors->get('videoUpload')" class="mt-1" />
                        </div>
                    @else
                        <div>
                            <x-input-label value="Bunny Video ID" />
                            <x-text-input wire:model="bunnyVideoId" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('bunnyVideoId')" />
                            <x-input-error :messages="$errors->get('bunny_video_id')" />
                        </div>
                    @endif

                    @if ($bunnyVideoId !== '')
                        <p class="text-sm text-green-800">
                            فيديو مرتبط:
                            <span class="font-mono">{{ $bunnyVideoId }}</span>
                        </p>
                    @endif
                    @if ($uploadStatus !== '')
                        <p class="text-sm text-brand-800">{{ $uploadStatus }}</p>
                    @endif
                </div>
            @endif

            <div>
                <x-input-label value="المحتوى النصي" />
                <textarea wire:model="body" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="isPublished" class="rounded border-gray-300 text-brand-700 shadow-sm">
                نشر مباشرة
            </label>

            <div>
                <x-primary-button>حفظ الدرس</x-primary-button>
            </div>
        </form>

        <div class="space-y-2">
            <h4 class="font-medium">دروس الوحدة المختارة</h4>
            @forelse ($lessons as $lesson)
                <div class="space-y-3 rounded-xl border border-slate-200 px-3 py-3">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm">
                        <span class="font-medium">{{ $lesson->title }}</span>
                        <span class="text-gray-500">— {{ $lesson->type->label() }}</span>
                        @if ($lesson->is_published)
                            <span class="text-green-600">منشور</span>
                        @else
                            <span class="text-amber-600">مسودة</span>
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
                <p class="text-sm text-gray-500">لا توجد دروس في هذه الوحدة بعد.</p>
            @endforelse
        </div>
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
