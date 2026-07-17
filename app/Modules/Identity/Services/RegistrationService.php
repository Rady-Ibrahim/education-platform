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
    public function __construct(
        private readonly TeacherProfileService $teacherProfiles,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     phone?: string|null,
     *     role: string,
     *     headline?: string|null,
     *     bio?: string|null,
     *     vodafone_cash_number?: string|null,
     *     payment_instructions?: string|null,
     *     is_publicly_visible?: bool,
     *     subject_ids?: list<int>
     * }  $data
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
                'status' => UserStatus::Active,
                'approved_at' => now(),
            ];

            if ($role === UserRole::Student) {
                $payload['student_code'] = app(StudentCodeService::class)->generate();
            }

            if ($role === UserRole::Teacher) {
                $payload['slug'] = $this->teacherProfiles->uniqueSlug($data['name']);
                $payload['headline'] = ($data['headline'] ?? null) ?: null;
                $payload['bio'] = ($data['bio'] ?? null) ?: null;
                $payload['vodafone_cash_number'] = ($data['vodafone_cash_number'] ?? null) ?: null;
                $payload['payment_instructions'] = ($data['payment_instructions'] ?? null) ?: null;
                $payload['is_publicly_visible'] = false;
            }

            $user = User::query()->create($payload);
            $user->assignRole($role);

            if ($role === UserRole::Teacher) {
                $this->teacherProfiles->update($user, [
                    'headline' => $payload['headline'],
                    'bio' => $payload['bio'],
                    'vodafone_cash_number' => $payload['vodafone_cash_number'],
                    'payment_instructions' => $payload['payment_instructions'],
                    'is_publicly_visible' => (bool) ($data['is_publicly_visible'] ?? false),
                    'subject_ids' => $data['subject_ids'] ?? [],
                ]);
            }

            event(new Registered($user->fresh()));

            return $user->fresh();
        });
    }
}
