<?php

namespace App\Modules\Content\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Models\Lesson;
use Illuminate\Validation\ValidationException;

class ContentAccessService
{
    public function teacherOwnsSubject(User $teacher, Subject $subject): bool
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            return false;
        }

        return $subject->teachers()->where('users.id', $teacher->id)->exists();
    }

    public function teacherCanManageLesson(User $teacher, Lesson $lesson): bool
    {
        $lesson->loadMissing('unit.subject');

        return $this->teacherOwnsSubject($teacher, $lesson->unit->subject);
    }

    /**
     * وصول الطالب للمحتوى: مرتبط بمدرس يدرّس المادة.
     * (الاشتراك المدفوع سيُضاف كشرط إضافي في مرحلة المدفوعات)
     */
    public function studentCanAccessSubject(User $student, Subject $subject): bool
    {
        if (! $student->hasRole(UserRole::Student) || ! $student->isActive()) {
            return false;
        }

        return $subject->teachers()
            ->whereIn('users.id', $student->teachers()->pluck('users.id'))
            ->exists();
    }

    public function studentCanAccessLesson(User $student, Lesson $lesson): bool
    {
        $lesson->loadMissing('unit.subject');

        if (! $lesson->is_published) {
            return false;
        }

        return $this->studentCanAccessSubject($student, $lesson->unit->subject);
    }

    public function assertTeacherOwnsSubject(User $teacher, Subject $subject): void
    {
        if (! $this->teacherOwnsSubject($teacher, $subject)) {
            throw ValidationException::withMessages([
                'subject' => 'غير مصرح بإدارة محتوى هذه المادة.',
            ]);
        }
    }

    public function assertStudentCanAccessLesson(User $student, Lesson $lesson): void
    {
        if (! $this->studentCanAccessLesson($student, $lesson)) {
            throw ValidationException::withMessages([
                'lesson' => 'غير مصرح بمشاهدة هذا الدرس.',
            ]);
        }
    }
}
