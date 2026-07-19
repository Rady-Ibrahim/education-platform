<?php

namespace App\Modules\Identity\Services;

use App\Enums\ParentLinkStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Models\TeacherParentMessage;
use App\Notifications\TeacherParentMessageNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherParentMessageService
{
    /**
     * @return TeacherParentMessage
     */
    public function send(
        User $teacher,
        User $parent,
        string $body,
        ?User $student = null,
        ?UploadedFile $image = null,
    ): TeacherParentMessage {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'غير مصرح بإرسال الرسالة.',
            ]);
        }

        if (! $parent->hasRole(UserRole::Parent) || ! $parent->isActive()) {
            throw ValidationException::withMessages([
                'parent' => 'حساب ولي الأمر غير صالح.',
            ]);
        }

        $body = trim($body);
        if ($body === '' && ! $image) {
            throw ValidationException::withMessages([
                'body' => 'اكتب رسالة أو ارفع صورة.',
            ]);
        }

        if ($student) {
            $this->assertTeacherOwnsStudent($teacher, $student);
            $this->assertParentLinkedToStudent($parent, $student);
        } else {
            $this->assertTeacherCanMessageParent($teacher, $parent);
        }

        return DB::transaction(function () use ($teacher, $parent, $student, $body, $image) {
            $path = null;
            $disk = null;

            if ($image) {
                $disk = 'public';
                $path = $image->store('teacher-parent-messages', $disk);
            }

            $message = TeacherParentMessage::query()->create([
                'teacher_id' => $teacher->id,
                'parent_id' => $parent->id,
                'student_id' => $student?->id,
                'body' => $body !== '' ? $body : 'صورة مرفقة',
                'image_path' => $path,
                'image_disk' => $disk,
            ]);

            $parent->notify(new TeacherParentMessageNotification($message));

            return $message;
        });
    }

    public function markRead(User $parent, TeacherParentMessage $message): TeacherParentMessage
    {
        if ($message->parent_id !== $parent->id) {
            throw ValidationException::withMessages([
                'message' => 'غير مصرح.',
            ]);
        }

        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return $message->refresh();
    }

    private function assertTeacherOwnsStudent(User $teacher, User $student): void
    {
        if (! $teacher->students()->where('users.id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مرتبط بك.',
            ]);
        }
    }

    private function assertParentLinkedToStudent(User $parent, User $student): void
    {
        $linked = ParentStudentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', ParentLinkStatus::Active)
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'parent' => 'ولي الأمر غير مرتبط بهذا الطالب.',
            ]);
        }
    }

    private function assertTeacherCanMessageParent(User $teacher, User $parent): void
    {
        $studentIds = $teacher->students()->pluck('users.id');

        $linked = ParentStudentLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', ParentLinkStatus::Active)
            ->whereIn('student_id', $studentIds)
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'parent' => 'لا يوجد ابن مشترك بينك وبين ولي الأمر.',
            ]);
        }
    }
}
