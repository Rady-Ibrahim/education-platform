<?php

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Modules\Content\Models\Lesson;
use Illuminate\Validation\ValidationException;

class LessonPlaybackService
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly BunnyStreamService $bunny,
    ) {}

    public function signedEmbedUrl(User $student, Lesson $lesson): string
    {
        $this->access->assertStudentCanAccessLesson($student, $lesson);

        if (! $lesson->hasVideo()) {
            throw ValidationException::withMessages([
                'lesson' => 'هذا الدرس لا يحتوي على فيديو.',
            ]);
        }

        if (! $this->bunny->isConfigured()) {
            throw ValidationException::withMessages([
                'bunny' => 'إعدادات Bunny غير مكتملة.',
            ]);
        }

        return $this->bunny->embedUrl($lesson->bunny_video_id);
    }
}
