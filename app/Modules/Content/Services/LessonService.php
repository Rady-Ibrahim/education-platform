<?php

namespace App\Modules\Content\Services;

use App\Enums\LessonType;
use App\Models\User;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Models\LessonAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class LessonService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    /**
     * @param  array{title: string, type?: string, body?: string|null, bunny_video_id?: string|null, meeting_url?: string|null, scheduled_at?: string|\DateTimeInterface|null, ordering?: int|null, duration_seconds?: int|null, is_published?: bool}  $data
     */
    public function create(User $teacher, Unit $unit, array $data): Lesson
    {
        $unit->loadMissing('subject');
        $this->access->assertTeacherOwnsSubject($teacher, $unit->subject);

        $type = LessonType::from($data['type'] ?? LessonType::Text->value);
        $this->assertTypeRules($type, $data['bunny_video_id'] ?? null, $data['meeting_url'] ?? null);

        return Lesson::query()->create([
            'unit_id' => $unit->id,
            'created_by' => $teacher->id,
            'title' => $data['title'],
            'type' => $type,
            'body' => $data['body'] ?? null,
            'bunny_video_id' => $type === LessonType::Live ? null : ($data['bunny_video_id'] ?? null),
            'meeting_url' => $type === LessonType::Live ? ($data['meeting_url'] ?? null) : null,
            'scheduled_at' => $type === LessonType::Live ? ($data['scheduled_at'] ?? null) : null,
            'ordering' => $data['ordering'] ?? ((int) $unit->lessons()->max('ordering') + 1),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'is_published' => $data['is_published'] ?? false,
        ]);
    }

    /**
     * @param  array{title?: string, type?: string, body?: string|null, bunny_video_id?: string|null, meeting_url?: string|null, scheduled_at?: string|\DateTimeInterface|null, ordering?: int|null, duration_seconds?: int|null, is_published?: bool}  $data
     */
    public function update(User $teacher, Lesson $lesson, array $data): Lesson
    {
        $lesson->loadMissing('unit.subject');
        $this->access->assertTeacherOwnsSubject($teacher, $lesson->unit->subject);

        $type = isset($data['type']) ? LessonType::from($data['type']) : $lesson->type;
        $videoId = array_key_exists('bunny_video_id', $data) ? $data['bunny_video_id'] : $lesson->bunny_video_id;
        $meetingUrl = array_key_exists('meeting_url', $data) ? $data['meeting_url'] : $lesson->meeting_url;
        $this->assertTypeRules($type, $videoId, $meetingUrl);

        $lesson->update([
            'title' => $data['title'] ?? $lesson->title,
            'type' => $type,
            'body' => array_key_exists('body', $data) ? $data['body'] : $lesson->body,
            'bunny_video_id' => $type === LessonType::Live ? null : $videoId,
            'meeting_url' => $type === LessonType::Live ? $meetingUrl : null,
            'scheduled_at' => $type === LessonType::Live
                ? (array_key_exists('scheduled_at', $data) ? $data['scheduled_at'] : $lesson->scheduled_at)
                : null,
            'ordering' => $data['ordering'] ?? $lesson->ordering,
            'duration_seconds' => array_key_exists('duration_seconds', $data) ? $data['duration_seconds'] : $lesson->duration_seconds,
            'is_published' => $data['is_published'] ?? $lesson->is_published,
        ]);

        return $lesson->refresh();
    }

    public function publish(User $teacher, Lesson $lesson, bool $published = true): Lesson
    {
        return $this->update($teacher, $lesson, ['is_published' => $published]);
    }

    public function delete(User $teacher, Lesson $lesson): void
    {
        $lesson->loadMissing('unit.subject');
        $this->access->assertTeacherOwnsSubject($teacher, $lesson->unit->subject);
        $lesson->delete();
    }

    public function addAttachment(
        User $teacher,
        Lesson $lesson,
        UploadedFile $file,
        bool $isDownloadable = true,
    ): LessonAttachment {
        $lesson->loadMissing('unit.subject');
        $this->access->assertTeacherOwnsSubject($teacher, $lesson->unit->subject);

        $path = $file->store('lesson-attachments/'.$lesson->id, 'public');

        return $lesson->attachments()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'is_downloadable' => $isDownloadable,
        ]);
    }

    private function assertTypeRules(LessonType $type, ?string $videoId, ?string $meetingUrl): void
    {
        if (in_array($type, [LessonType::Video, LessonType::Mixed], true) && blank($videoId)) {
            throw ValidationException::withMessages([
                'bunny_video_id' => 'معرف فيديو Bunny مطلوب لهذا النوع من الدروس.',
            ]);
        }

        if ($type === LessonType::Live && blank($meetingUrl)) {
            throw ValidationException::withMessages([
                'meeting_url' => 'رابط الحصة (زوم / ميت / أي رابط) مطلوب.',
            ]);
        }
    }
}
