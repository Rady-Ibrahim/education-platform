<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\Payment;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Teacher)) {
            return $payment->teacher_id === $user->id;
        }

        if ($user->hasRole(UserRole::Student)) {
            return $payment->student_id === $user->id;
        }

        if ($user->hasRole(UserRole::Parent)) {
            return app(\App\Modules\Identity\Services\ParentLinkService::class)
                ->parentCanViewStudent($user, $payment->student);
        }

        return false;
    }

    public function review(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        return $user->hasRole(UserRole::Teacher) && $payment->teacher_id === $user->id;
    }
}
