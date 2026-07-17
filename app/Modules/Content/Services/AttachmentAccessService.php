<?php

namespace App\Modules\Content\Services;

use App\Models\User;
use App\Modules\Content\Models\LessonAttachment;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AttachmentAccessService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    public function signedDownloadUrl(User $student, LessonAttachment $attachment, int $ttlMinutes = 15): string
    {
        $attachment->loadMissing('lesson.unit.subject');

        if (! $attachment->is_downloadable) {
            throw ValidationException::withMessages([
                'attachment' => 'هذا المرفق غير قابل للتحميل.',
            ]);
        }

        $this->access->assertStudentCanAccessLesson($student, $attachment->lesson);

        return URL::temporarySignedRoute(
            'student.attachments.download',
            now()->addMinutes($ttlMinutes),
            ['attachment' => $attachment->id]
        );
    }

    public function assertCanDownload(User $student, LessonAttachment $attachment): void
    {
        $attachment->loadMissing('lesson');

        if (! $attachment->is_downloadable) {
            throw ValidationException::withMessages([
                'attachment' => 'هذا المرفق غير قابل للتحميل.',
            ]);
        }

        $this->access->assertStudentCanAccessLesson($student, $attachment->lesson);
    }
}
