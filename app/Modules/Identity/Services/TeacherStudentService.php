<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherStudentService
{
    /**
     * المدرس يضيف طالبًا يدويًا → الحساب نشط مباشرة (بدون انتظار الأدمن).
     *
     * @param  array{name: string, email: string, phone?: string|null, password?: string|null}  $data
     * @return array{user: User, plain_password: string}
     */
    public function createStudent(User $teacher, array $data): array
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير مصرح بإضافة طلاب.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $data) {
            $plainPassword = $data['password'] ?? Str::password(10);
            $branchId = $teacher->branch_id ?? Branch::defaultBranch()?->id;

            $student = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => ($data['phone'] ?? null) ?: null,
                'password' => $plainPassword,
                'branch_id' => $branchId,
                'created_by' => $teacher->id,
                'status' => UserStatus::Active,
                'approved_at' => now(),
                'approved_by' => $teacher->id,
                'student_code' => $this->generateStudentCode(),
                'email_verified_at' => now(),
            ]);

            $student->assignRole(UserRole::Student);

            $teacher->students()->syncWithoutDetaching([
                $student->id => ['joined_at' => now()],
            ]);

            return [
                'user' => $student->refresh(),
                'plain_password' => $plainPassword,
            ];
        });
    }

    private function generateStudentCode(): string
    {
        do {
            $code = 'STU-'.now()->format('y').'-'.Str::upper(Str::random(5));
        } while (User::query()->where('student_code', $code)->exists());

        return $code;
    }
}
