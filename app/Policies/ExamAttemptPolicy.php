<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Identity\Services\ParentLinkService;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Student)) {
            return $attempt->student_id === $user->id;
        }

        if ($user->hasRole(UserRole::Teacher)) {
            $attempt->loadMissing('exam');

            return $attempt->exam?->created_by === $user->id;
        }

        if ($user->hasRole(UserRole::Parent)) {
            $student = $attempt->relationLoaded('student')
                ? $attempt->student
                : User::query()->find($attempt->student_id);

            return $student
                ? app(ParentLinkService::class)->parentCanViewStudent($user, $student)
                : false;
        }

        return false;
    }

    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasRole(UserRole::Student) && $attempt->student_id === $user->id;
    }
}
