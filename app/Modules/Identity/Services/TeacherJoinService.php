<?php

namespace App\Modules\Identity\Services;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Models\TeacherJoinRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherJoinService
{
    public function requestJoin(User $student, User $teacher, ?string $message = null): TeacherJoinRequest
    {
        if (! $student->hasRole(UserRole::Student) || ! $student->isActive()) {
            throw ValidationException::withMessages([
                'student' => 'يجب أن يكون حساب الطالب نشطًا.',
            ]);
        }

        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير متاح للانضمام.',
            ]);
        }

        if ($student->teachers()->where('teacher_id', $teacher->id)->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'أنت منضم لهذا المدرس بالفعل.',
            ]);
        }

        $existing = TeacherJoinRequest::query()
            ->where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if ($existing?->status === JoinRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'teacher' => 'لديك طلب قيد المراجعة لهذا المدرس.',
            ]);
        }

        if ($existing?->status === JoinRequestStatus::Approved) {
            throw ValidationException::withMessages([
                'teacher' => 'تم قبول انضمامك مسبقًا.',
            ]);
        }

        if ($existing) {
            $existing->update([
                'status' => JoinRequestStatus::Pending,
                'message' => $message,
                'review_note' => null,
                'reviewed_at' => null,
            ]);

            return $existing->refresh();
        }

        return TeacherJoinRequest::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => JoinRequestStatus::Pending,
            'message' => $message,
        ]);
    }

    public function approve(TeacherJoinRequest $request, User $teacher, ?string $note = null): TeacherJoinRequest
    {
        $this->assertOwnsPendingRequest($request, $teacher);

        return DB::transaction(function () use ($request, $note) {
            $request->update([
                'status' => JoinRequestStatus::Approved,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            $request->teacher->students()->syncWithoutDetaching([
                $request->student_id => ['joined_at' => now()],
            ]);

            return $request->refresh();
        });
    }

    public function reject(TeacherJoinRequest $request, User $teacher, ?string $note = null): TeacherJoinRequest
    {
        $this->assertOwnsPendingRequest($request, $teacher);

        $request->update([
            'status' => JoinRequestStatus::Rejected,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        return $request->refresh();
    }

    private function assertOwnsPendingRequest(TeacherJoinRequest $request, User $teacher): void
    {
        if ($request->teacher_id !== $teacher->id || ! $teacher->hasRole(UserRole::Teacher)) {
            throw ValidationException::withMessages([
                'request' => 'غير مصرح بمراجعة هذا الطلب.',
            ]);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'الطلب ليس قيد الانتظار.',
            ]);
        }
    }
}
