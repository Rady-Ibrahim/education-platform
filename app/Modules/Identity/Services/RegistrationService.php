<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null, role: string}  $data
     */
    public function register(array $data): User
    {
        $role = UserRole::from($data['role']);

        if (! in_array($role, [UserRole::Student, UserRole::Teacher, UserRole::Parent], true)) {
            throw ValidationException::withMessages([
                'role' => 'يمكن التسجيل كطالب أو مدرس أو ولي أمر فقط.',
            ]);
        }

        return DB::transaction(function () use ($data, $role) {
            $branch = Branch::defaultBranch();

            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => ($data['phone'] ?? null) ?: null,
                'password' => $data['password'],
                'branch_id' => $branch?->id,
                'status' => UserStatus::PendingAdmin,
            ];

            if ($role === UserRole::Student) {
                $payload['student_code'] = app(StudentCodeService::class)->generate();
            }

            $user = User::query()->create($payload);

            $user->assignRole($role);
            event(new Registered($user));

            return $user;
        });
    }
}
