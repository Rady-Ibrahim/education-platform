<?php

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Models\LessonProgress;
use Illuminate\Validation\ValidationException;

class LessonProgressService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    public function updateProgress(
        User $student,
        Lesson $lesson,
        int $percent,
        ?int $watchedSeconds = null,
    ): LessonProgress {
        $this->access->assertStudentCanAccessLesson($student, $lesson);

        $percent = max(0, min(100, $percent));
        $isCompleted = $percent >= 100;

        $progress = LessonProgress::query()->firstOrNew([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
        ]);

        $progress->percent = max($progress->percent ?? 0, $percent);
        $progress->watched_seconds = max($progress->watched_seconds ?? 0, $watchedSeconds ?? 0);
        $progress->last_watched_at = now();

        if ($isCompleted && ! $progress->is_completed) {
            $progress->is_completed = true;
            $progress->completed_at = now();
            $progress->percent = 100;
        }

        $progress->save();

        return $progress->refresh();
    }

    public function markCompleted(User $student, Lesson $lesson): LessonProgress
    {
        return $this->updateProgress($student, $lesson, 100);
    }
}
