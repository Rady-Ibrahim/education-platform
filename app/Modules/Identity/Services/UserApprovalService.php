<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserApprovalService
{
    public function approve(User $user, User $admin, ?string $note = null): User
    {
        $this->assertAdmin($admin);
        $this->assertPending($user);

        $user->forceFill([
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejection_reason' => null,
        ])->save();

        return $user->refresh();
    }

    public function reject(User $user, User $admin, string $reason): User
    {
        $this->assertAdmin($admin);
        $this->assertPending($user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'سبب الرفض مطلوب.',
            ]);
        }

        $user->forceFill([
            'status' => UserStatus::Rejected,
            'approved_at' => null,
            'approved_by' => $admin->id,
            'rejection_reason' => $reason,
        ])->save();

        return $user->refresh();
    }

    public function suspend(User $user, User $admin, ?string $reason = null): User
    {
        $this->assertAdmin($admin);

        $user->forceFill([
            'status' => UserStatus::Suspended,
            'rejection_reason' => $reason,
        ])->save();

        return $user->refresh();
    }

    private function assertAdmin(User $admin): void
    {
        if (! $admin->hasRole(UserRole::Admin)) {
            throw ValidationException::withMessages([
                'admin' => 'غير مصرح.',
            ]);
        }
    }

    private function assertPending(User $user): void
    {
        if ($user->status !== UserStatus::PendingAdmin) {
            throw ValidationException::withMessages([
                'user' => 'الحساب ليس بانتظار الموافقة.',
            ]);
        }
    }
}
