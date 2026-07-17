<?php

namespace App\Livewire\Teacher;

use App\Enums\LessonType;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Services\BunnyStreamService;
use App\Modules\Content\Services\LessonService;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class ManageLessons extends Component
{
    use WithFileUploads;

    public ?int $subjectId = null;

    public ?int $unitId = null;

    public string $title = '';

    public string $type = 'text';

    public string $body = '';

    public string $bunnyVideoId = '';

    public string $meetingUrl = '';

    public string $scheduledAt = '';

    public bool $isPublished = false;

    /** @var mixed */
    public $videoUpload = null;

    public string $videoSource = 'upload';

    public string $uploadStatus = '';

    public ?int $attachLessonId = null;

    /** @var mixed */
    public $attachmentUpload = null;

    public bool $attachmentDownloadable = true;

    public function mount(): void
    {
        $subject = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->first();

        $this->subjectId = $subject?->id;
        $this->unitId = $subject?->units()->orderBy('ordering')->value('id');
    }

    public function updatedSubjectId(): void
    {
        $this->unitId = Unit::query()
            ->where('subject_id', $this->subjectId)
            ->orderBy('ordering')
            ->value('id');
    }

    public function updatedVideoUpload(BunnyStreamService $bunny): void
    {
        if (! $this->videoUpload) {
            return;
        }

        $this->uploadVideoToBunny($bunny);
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
        $validated = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'unitId' => ['required', 'exists:units,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,video,mixed,live'],
            'body' => ['nullable', 'string'],
            'bunnyVideoId' => ['nullable', 'string', 'max:255'],
            'meetingUrl' => ['nullable', 'url', 'max:500'],
            'scheduledAt' => ['nullable', 'date'],
            'isPublished' => ['boolean'],
        ]);

        $unit = Unit::query()->with('subject')->findOrFail($validated['unitId']);

        $service->create(auth()->user(), $unit, [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'body' => $validated['body'] ?: null,
            'bunny_video_id' => $validated['bunnyVideoId'] ?: null,
            'meeting_url' => $validated['meetingUrl'] ?: null,
            'scheduled_at' => $validated['scheduledAt'] ?: null,
            'is_published' => $validated['isPublished'],
        ]);

        $this->reset(['title', 'body', 'bunnyVideoId', 'meetingUrl', 'scheduledAt', 'isPublished', 'videoUpload', 'uploadStatus']);
        $this->type = 'text';
        $this->videoSource = 'upload';
        session()->flash('lesson_status', 'تم إنشاء الدرس.');
    }

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

    public function uploadAttachmentFor(int $lessonId, LessonService $service): void
    {
        $this->attachLessonId = $lessonId;

        $this->validate([
            'attachLessonId' => ['required', 'exists:lessons,id'],
            'attachmentUpload' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg', 'max:20480'],
            'attachmentDownloadable' => ['boolean'],
        ]);

        $lesson = Lesson::query()->findOrFail($lessonId);
        $service->addAttachment(
            auth()->user(),
            $lesson,
            $this->attachmentUpload,
            $this->attachmentDownloadable,
        );

        $this->reset(['attachLessonId', 'attachmentUpload']);
        $this->attachmentDownloadable = true;
        session()->flash('lesson_status', 'تم رفع المرفق للدرس.');
    }

    public function render(BunnyStreamService $bunny)
    {
        $subjects = Subject::query()
            ->with(['grade.stage', 'units'])
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->get();

        $units = Unit::query()
            ->where('subject_id', $this->subjectId)
            ->orderBy('ordering')
            ->get();

        $lessons = Lesson::query()
            ->with(['unit', 'attachments'])
            ->where('unit_id', $this->unitId)
            ->orderBy('ordering')
            ->get();

        $maxMb = (int) config('bunny.max_upload_mb', 512);

        return view('livewire.teacher.manage-lessons', [
            'subjects' => $subjects,
            'units' => $units,
            'lessons' => $lessons,
            'types' => LessonType::cases(),
            'canUploadVideo' => $bunny->canUpload(),
            'maxUploadMb' => $maxMb,
        ]);
    }

    private function uploadVideoToBunny(BunnyStreamService $bunny): void
    {
        $maxKb = (int) config('bunny.max_upload_mb', 512) * 1024;

        $this->validate([
            'videoUpload' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska', 'max:'.$maxKb],
        ]);

        if (! $bunny->canUpload()) {
            $this->addError('videoUpload', 'رفع الفيديو غير مفعّل. اضبط BUNNY_STREAM_API_KEY أو الصق Bunny Video ID يدويًا.');

            return;
        }

        try {
            $this->uploadStatus = 'جاري الرفع إلى Bunny…';
            $title = $this->title !== '' ? $this->title : 'درس '.now()->format('Y-m-d H:i');
            $this->bunnyVideoId = $bunny->createAndUpload($title, $this->videoUpload);
            $this->uploadStatus = 'تم الرفع. معرّف الفيديو: '.$this->bunnyVideoId;
            if (! in_array($this->type, ['video', 'mixed'], true)) {
                $this->type = 'video';
            }
            $this->reset('videoUpload');
        } catch (RuntimeException $e) {
            $this->uploadStatus = '';
            $this->addError('videoUpload', $e->getMessage());
        }
    }
}
