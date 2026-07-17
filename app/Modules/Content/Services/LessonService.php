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
     * @param  array{title: string, type?: string, body?: string|null, bunny_video_id?: string|null, ordering?: int|null, duration_seconds?: int|null, is_published?: bool}  $data
     */
    public function create(User $teacher, Unit $unit, array $data): Lesson
    {
        $unit->loadMissing('subject');
        $this->access->assertTeacherOwnsSubject($teacher, $unit->subject);

        $type = LessonType::from($data['type'] ?? LessonType::Text->value);
        $this->assertVideoRules($type, $data['bunny_video_id'] ?? null);

        return Lesson::query()->create([
            'unit_id' => $unit->id,
            'created_by' => $teacher->id,
            'title' => $data['title'],
            'type' => $type,
            'body' => $data['body'] ?? null,
            'bunny_video_id' => $data['bunny_video_id'] ?? null,
            'ordering' => $data['ordering'] ?? ((int) $unit->lessons()->max('ordering') + 1),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'is_published' => $data['is_published'] ?? false,
        ]);
    }

    /**
     * @param  array{title?: string, type?: string, body?: string|null, bunny_video_id?: string|null, ordering?: int|null, duration_seconds?: int|null, is_published?: bool}  $data
     */
    public function update(User $teacher, Lesson $lesson, array $data): Lesson
    {
        $lesson->loadMissing('unit.subject');
        $this->access->assertTeacherOwnsSubject($teacher, $lesson->unit->subject);

        $type = isset($data['type']) ? LessonType::from($data['type']) : $lesson->type;
        $videoId = array_key_exists('bunny_video_id', $data) ? $data['bunny_video_id'] : $lesson->bunny_video_id;
        $this->assertVideoRules($type, $videoId);

        $lesson->update([
            'title' => $data['title'] ?? $lesson->title,
            'type' => $type,
            'body' => array_key_exists('body', $data) ? $data['body'] : $lesson->body,
            'bunny_video_id' => $videoId,
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

    private function assertVideoRules(LessonType $type, ?string $videoId): void
    {
        if (in_array($type, [LessonType::Video, LessonType::Mixed], true) && blank($videoId)) {
            throw ValidationException::withMessages([
                'bunny_video_id' => 'معرف فيديو Bunny مطلوب لهذا النوع من الدروس.',
            ]);
        }
    }
}
