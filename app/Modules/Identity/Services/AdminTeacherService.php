<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Payments\Services\PlatformBillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminTeacherService
{
    public function __construct(
        private readonly TeacherProfileService $profiles,
    ) {}

    /**
     * Admin creates a teacher account directly — account is Active immediately.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone?: string|null,
     *     password?: string|null,
     *     headline?: string|null,
     *     vodafone_cash_number?: string|null,
     *     subject_mode?: 'catalog'|'custom'|null,
     *     subject_id?: int|null,
     *     grade_id?: int|null,
     *     subject_name?: string|null,
     * } $data
     * @return array{user: User, plain_password: string}
     */
    public function createTeacher(User $admin, array $data): array
    {
        if (! $admin->hasRole(UserRole::Admin)) {
            throw ValidationException::withMessages([
                'admin' => 'غير مصرح بإنشاء حسابات مدرسين.',
            ]);
        }

        return DB::transaction(function () use ($admin, $data) {
            $plainPassword = $data['password'] ?? Str::password(12);
            $branch        = Branch::defaultBranch();

            $teacher = User::query()->create([
                'name'               => $data['name'],
                'email'              => $data['email'],
                'phone'              => ($data['phone'] ?? null) ?: null,
                'password'           => $plainPassword,
                'branch_id'          => $branch?->id,
                'created_by'         => $admin->id,
                'status'             => UserStatus::Active,
                'approved_at'        => now(),
                'approved_by'        => $admin->id,
                'email_verified_at'  => now(),
                'headline'           => ($data['headline'] ?? null) ?: null,
                'vodafone_cash_number' => ($data['vodafone_cash_number'] ?? null) ?: null,
                'is_publicly_visible' => false,
                'slug'               => $this->profiles->uniqueSlug($data['name']),
            ]);

            $teacher->assignRole(UserRole::Teacher);

            // Assign subject if provided
            $subjectMode = $data['subject_mode'] ?? null;

            if ($subjectMode === 'catalog' && ! empty($data['subject_id'])) {
                $subject = Subject::query()
                    ->whereKey((int) $data['subject_id'])
                    ->where('is_active', true)
                    ->first();

                if ($subject) {
                    $teacher->teachingSubjects()->sync([$subject->id]);
                }
            } elseif ($subjectMode === 'custom' && ! empty($data['subject_name']) && ! empty($data['grade_id'])) {
                $grade = Grade::query()->whereKey((int) $data['grade_id'])->where('is_active', true)->first();
                if ($grade) {
                    $subject = Subject::query()->create([
                        'grade_id'   => $grade->id,
                        'name'       => trim($data['subject_name']),
                        'code'       => Str::upper(Str::slug(trim($data['subject_name']), '_')),
                        'ordering'   => ((int) $grade->subjects()->max('ordering') + 1),
                        'is_active'  => true,
                        'created_by' => $teacher->id,
                        'is_custom'  => true,
                    ]);
                    Unit::query()->create([
                        'subject_id' => $subject->id,
                        'name'       => 'الوحدة الأولى',
                        'ordering'   => 1,
                        'is_active'  => true,
                    ]);
                    $teacher->teachingSubjects()->sync([$subject->id]);
                }
            }

            // Start platform billing trial from account creation date
            app(PlatformBillingService::class)->ensureSubscription($teacher->fresh());

            return [
                'user'           => $teacher->fresh(),
                'plain_password' => $plainPassword,
            ];
        });
    }
}
