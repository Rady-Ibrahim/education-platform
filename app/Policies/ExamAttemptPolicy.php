<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Exams\Models\ExamAttempt;

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

        return false;
    }

    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $user->hasRole(UserRole::Student) && $attempt->student_id === $user->id;
    }
}
