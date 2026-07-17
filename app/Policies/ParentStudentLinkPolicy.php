<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Services\ParentLinkService;

class ParentStudentLinkPolicy
{
    public function view(User $user, ParentStudentLink $link): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Parent)) {
            return $link->parent_id === $user->id;
        }

        if ($user->hasRole(UserRole::Student)) {
            return $link->student_id === $user->id;
        }

        if ($user->hasRole(UserRole::Teacher)) {
            return $user->students()->where('users.id', $link->student_id)->exists();
        }

        return false;
    }

    public function viewStudent(User $user, User $student): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Parent)) {
            return app(ParentLinkService::class)->parentCanViewStudent($user, $student);
        }

        return false;
    }
}
