<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Certificates\Models\Certificate;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        return $user->hasRole(UserRole::Student) && $certificate->student_id === $user->id;
    }
}
