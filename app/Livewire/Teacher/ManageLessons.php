<?php

namespace App\Livewire\Teacher;

use App\Enums\LessonType;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Models\LessonAttachment;
use App\Modules\Content\Services\BunnyStreamService;
use App\Modules\Content\Services\LessonService;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class ManageLessons extends Component
{
    use WithFileUploads;

    // ── Subject / Unit picker ──────────────────────────────────────────────────

    public ?int $subjectId = null;
    public ?int $unitId    = null;

    // ── Create Unit modal ─────────────────────────────────────────────────────

    public bool $showCreateUnit = false;
    public string $newUnitName  = '';

    // ── Create Lesson form ────────────────────────────────────────────────────

    public string $title       = '';
    public string $type        = 'text';
    public string $body        = '';
    public string $bunnyVideoId = '';
    public string $meetingUrl  = '';
    public string $scheduledAt = '';
    public bool   $isPublished = false;

    /** @var mixed Livewire file upload for video */
    public $videoUpload = null;

    public string $videoSource    = 'upload';
    public string $uploadStatus   = '';
    public int    $uploadProgress = 0;

    // ── Edit Lesson modal ─────────────────────────────────────────────────────

    public bool    $showEditLesson   = false;
    public ?int    $editingLessonId  = null;
    public string  $editTitle        = '';
    public string  $editType         = 'text';
    public string  $editBody         = '';
    public string  $editBunnyVideoId = '';
    public string  $editMeetingUrl   = '';
    public string  $editScheduledAt  = '';
    public bool    $editIsPublished  = false;

    // ── Per-lesson attachment upload ──────────────────────────────────────────

    public ?int $attachOpenForLesson    = null;
    public $attachmentUpload            = null;
    public bool $attachmentDownloadable = true;

    // ──────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $subject = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->first();

        $this->subjectId = $subject?->id;
        $this->unitId    = $subject?->units()->orderBy('ordering')->value('id');
    }

    public function updatedSubjectId(): void
    {
        $this->unitId = Unit::query()
            ->where('subject_id', $this->subjectId)
            ->orderBy('ordering')
            ->value('id');
        $this->showCreateUnit = false;
    }

    // ── Units ─────────────────────────────────────────────────────────────────

    public function openCreateUnit(): void
    {
        $this->showCreateUnit = true;
        $this->newUnitName    = '';
    }

    public function saveUnit(): void
    {
        $this->validate([
            'subjectId'   => ['required', 'exists:subjects,id'],
            'newUnitName' => ['required', 'string', 'max:120'],
        ]);

        $subject = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->findOrFail($this->subjectId);

        $unit = $subject->units()->create([
            'name'     => trim($this->newUnitName),
            'ordering' => ($subject->units()->max('ordering') ?? 0) + 1,
        ]);

        $this->unitId         = $unit->id;
        $this->showCreateUnit = false;
        $this->newUnitName    = '';
        session()->flash('lesson_status', 'تم إنشاء الوحدة: ' . $unit->name);
    }

    public function cancelCreateUnit(): void
    {
        $this->showCreateUnit = false;
        $this->newUnitName    = '';
    }

    // ── Create Lesson ─────────────────────────────────────────────────────────

    public function updatedVideoUpload(BunnyStreamService $bunny): void
    {
        if ($this->videoUpload) {
            $this->uploadVideoToBunny($bunny);
        }
    }

    public function processRecordedUpload(BunnyStreamService $bunny): void
    {
        if (! $this->videoUpload) {
            $this->addError('videoUpload', 'لا يوجد تسجيل للرفع.');
            return;
        }
        $this->uploadVideoToBunny($bunny);
    }

    public function save(LessonService $service): void
    {
        $rules = [
            'subjectId'    => ['required', 'exists:subjects,id'],
            'unitId'       => ['required', 'exists:units,id'],
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:text,video,mixed,live'],
            'body'         => ['nullable', 'string'],
            'bunnyVideoId' => ['nullable', 'string', 'max:255'],
            'meetingUrl'   => ['nullable', 'url', 'max:500'],
            'scheduledAt'  => ['nullable', 'date'],
            'isPublished'  => ['boolean'],
        ];

        // Google Meet / Zoom URL is required when type is live
        if ($this->type === 'live') {
            $rules['meetingUrl'] = ['required', 'url', 'max:500'];
        }

        $validated = $this->validate($rules, [
            'meetingUrl.required' => 'رابط الحصة مطلوب للدروس المباشرة.',
            'meetingUrl.url'      => 'الرابط غير صالح — يجب أن يبدأ بـ https://',
            'unitId.required'     => 'اختر وحدة أو أنشئ وحدة جديدة أولاً.',
        ]);

        $unit = Unit::query()->with('subject')->findOrFail($validated['unitId']);

        $service->create(auth()->user(), $unit, [
            'title'          => $validated['title'],
            'type'           => $validated['type'],
            'body'           => $validated['body'] ?: null,
            'bunny_video_id' => $validated['bunnyVideoId'] ?: null,
            'meeting_url'    => $validated['meetingUrl'] ?: null,
            'scheduled_at'   => $validated['scheduledAt'] ?: null,
            'is_published'   => $validated['isPublished'],
        ]);

        $this->reset([
            'title', 'body', 'bunnyVideoId', 'meetingUrl', 'scheduledAt',
            'isPublished', 'videoUpload', 'uploadStatus', 'uploadProgress',
        ]);
        $this->type        = 'text';
        $this->videoSource = 'upload';
        session()->flash('lesson_status', 'تم إنشاء الدرس.');
    }

    // ── Edit Lesson ───────────────────────────────────────────────────────────

    public function openEditLesson(int $lessonId): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        abort_unless($lesson->created_by === auth()->id(), 403);

        $this->editingLessonId  = $lesson->id;
        $this->editTitle        = $lesson->title;
        $this->editType         = $lesson->type->value;
        $this->editBody         = (string) ($lesson->body ?? '');
        $this->editBunnyVideoId = (string) ($lesson->bunny_video_id ?? '');
        $this->editMeetingUrl   = (string) ($lesson->meeting_url ?? '');
        $this->editScheduledAt  = $lesson->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->editIsPublished  = $lesson->is_published;
        $this->showEditLesson   = true;
    }

    public function updateLesson(LessonService $service): void
    {
        if (! $this->editingLessonId) {
            return;
        }

        $rules = [
            'editTitle'        => ['required', 'string', 'max:255'],
            'editType'         => ['required', 'in:text,video,mixed,live'],
            'editBody'         => ['nullable', 'string'],
            'editBunnyVideoId' => ['nullable', 'string', 'max:255'],
            'editMeetingUrl'   => ['nullable', 'url', 'max:500'],
            'editScheduledAt'  => ['nullable', 'date'],
            'editIsPublished'  => ['boolean'],
        ];

        // Google Meet / Zoom URL is required when type is live
        if ($this->editType === 'live') {
            $rules['editMeetingUrl'] = ['required', 'url', 'max:500'];
        }

        $validated = $this->validate($rules, [
            'editMeetingUrl.required' => 'رابط الحصة مطلوب للدروس المباشرة.',
            'editMeetingUrl.url'      => 'الرابط غير صالح — يجب أن يبدأ بـ https://',
        ]);

        $lesson = Lesson::query()->findOrFail($this->editingLessonId);

        $service->update(auth()->user(), $lesson, [
            'title'          => $validated['editTitle'],
            'type'           => $validated['editType'],
            'body'           => $validated['editBody'] ?: null,
            'bunny_video_id' => $validated['editBunnyVideoId'] ?: null,
            'meeting_url'    => $validated['editMeetingUrl'] ?: null,
            'scheduled_at'   => $validated['editScheduledAt'] ?: null,
            'is_published'   => $validated['editIsPublished'],
        ]);

        $this->closeEditLesson();
        session()->flash('lesson_status', 'تم تحديث الدرس: ' . $validated['editTitle']);
    }

    public function closeEditLesson(): void
    {
        $this->showEditLesson  = false;
        $this->editingLessonId = null;
        $this->reset([
            'editTitle', 'editType', 'editBody', 'editBunnyVideoId',
            'editMeetingUrl', 'editScheduledAt', 'editIsPublished',
        ]);
        $this->editType = 'text';
    }

    // ── Toggle publish / Delete ───────────────────────────────────────────────

    public function togglePublish(int $lessonId, LessonService $service): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $service->publish(auth()->user(), $lesson, ! $lesson->is_published);
        session()->flash('lesson_status', 'تم تحديث حالة النشر.');
    }

    public function deleteLesson(int $lessonId, LessonService $service): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $service->delete(auth()->user(), $lesson);
        session()->flash('lesson_status', 'تم حذف الدرس.');
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function openAttachPanel(int $lessonId): void
    {
        $this->attachOpenForLesson  = $this->attachOpenForLesson === $lessonId ? null : $lessonId;
        $this->attachmentUpload     = null;
        $this->attachmentDownloadable = true;
    }

    public function uploadAttachment(LessonService $service): void
    {
        $this->validate([
            'attachOpenForLesson'    => ['required', 'exists:lessons,id'],
            'attachmentUpload'       => ['required', 'file',
                                         'mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg',
                                         'max:20480'],
            'attachmentDownloadable' => ['boolean'],
        ]);

        $lesson = Lesson::query()->findOrFail($this->attachOpenForLesson);
        $service->addAttachment(
            auth()->user(),
            $lesson,
            $this->attachmentUpload,
            $this->attachmentDownloadable,
        );

        $this->attachmentUpload       = null;
        $this->attachmentDownloadable = true;
        session()->flash('lesson_status', 'تم رفع المرفق.');
    }

    public function deleteAttachment(int $attachmentId, LessonService $service): void
    {
        $attachment = LessonAttachment::query()->findOrFail($attachmentId);
        $service->deleteAttachment(auth()->user(), $attachment);
        session()->flash('lesson_status', 'تم حذف المرفق.');
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(BunnyStreamService $bunny)
    {
        $subjects = Subject::query()
            ->with(['grade.stage', 'units'])
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->get();

        $units = collect();
        if ($this->subjectId) {
            $units = Unit::query()
                ->where('subject_id', $this->subjectId)
                ->orderBy('ordering')
                ->get();
        }

        $lessons = collect();
        if ($this->unitId) {
            $lessons = Lesson::query()
                ->with(['unit', 'attachments'])
                ->where('unit_id', $this->unitId)
                ->orderBy('ordering')
                ->get();
        }

        return view('livewire.teacher.manage-lessons', [
            'subjects'       => $subjects,
            'units'          => $units,
            'lessons'        => $lessons,
            'types'          => LessonType::cases(),
            'canUploadVideo' => $bunny->canUpload(),
            'maxUploadMb'    => (int) config('bunny.max_upload_mb', 512),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function uploadVideoToBunny(BunnyStreamService $bunny): void
    {
        $maxKb = (int) config('bunny.max_upload_mb', 512) * 1024;

        $this->validate([
            'videoUpload' => [
                'required', 'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska',
                'max:' . $maxKb,
            ],
        ]);

        if (! $bunny->canUpload()) {
            $this->addError('videoUpload', 'رفع الفيديو غير مفعّل. اضبط BUNNY_STREAM_API_KEY أو الصق Bunny Video ID يدويًا.');
            return;
        }

        try {
            $this->uploadStatus   = 'جاري الرفع إلى Bunny…';
            $this->uploadProgress = 10;
            $title                = $this->title !== '' ? $this->title : 'درس ' . now()->format('Y-m-d H:i');
            $this->bunnyVideoId   = $bunny->createAndUpload($title, $this->videoUpload);
            $this->uploadStatus   = 'تم الرفع ✓';
            $this->uploadProgress = 100;

            if (! in_array($this->type, ['video', 'mixed'], true)) {
                $this->type = 'video';
            }

            $this->reset('videoUpload');
        } catch (RuntimeException $e) {
            $this->uploadStatus   = '';
            $this->uploadProgress = 0;
            $this->addError('videoUpload', $e->getMessage());
        }
    }
}
