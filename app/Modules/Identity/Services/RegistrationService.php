<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Academic\Services\AcademicStructureService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly TeacherProfileService $teacherProfiles,
        private readonly AcademicStructureService $academic,
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
     *     subject_mode?: 'catalog'|'custom'|null,
     *     subject_id?: int|null,
     *     grade_id?: int|null,
     *     subject_name?: string|null
     * }  $data
     */
    public function register(array $data): User
    {
        $role = UserRole::from($data['role']);

        if (! in_array($role, [UserRole::Student, UserRole::Teacher], true)) {
            throw ValidationException::withMessages([
                'role' => 'يمكن التسجيل كطالب أو مدرس فقط. ولي الأمر يُضاف بواسطة المدرس.',
            ]);
        }

        return DB::transaction(function () use ($data, $role) {
            $branch = Branch::defaultBranch();

            // Teachers need admin approval.
            // Students need teacher approval (PendingTeacher) — the join request is created
            // and the student can't access the platform until the teacher approves.
            $status = match ($role) {
                UserRole::Teacher => UserStatus::PendingAdmin,
                UserRole::Student => UserStatus::PendingTeacher,
                default           => UserStatus::Active,
            };

            $payload = [
                'name'        => $data['name'],
                'email'       => $data['email'],
                'phone'       => ($data['phone'] ?? null) ?: null,
                'password'    => $data['password'],
                'branch_id'   => $branch?->id,
                'status'      => $status,
                'approved_at' => null,
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

            if ($role === UserRole::Student) {
                $gradeId = (int) ($data['grade_id'] ?? 0);
                $grade = Grade::query()->whereKey($gradeId)->where('is_active', true)->first();
                if (! $grade) {
                    throw ValidationException::withMessages([
                        'grade_id' => 'اختر الصف الدراسي (مثلاً أولى ثانوي).',
                    ]);
                }
                // Enroll directly (bypasses isActive check) because student is PendingTeacher
                $user->grades()->sync([$grade->id => ['enrolled_at' => now()]]);
            }

            if ($role === UserRole::Teacher) {
                $profileData = [
                    'headline' => $payload['headline'],
                    'bio' => $payload['bio'],
                    'vodafone_cash_number' => $payload['vodafone_cash_number'],
                    'payment_instructions' => $payload['payment_instructions'],
                    'is_publicly_visible' => (bool) ($data['is_publicly_visible'] ?? false),
                    // Pass subject_mode=null so setSingleSubject is skipped
                    // (teacher is still PendingAdmin and assertActiveTeacher would throw).
                    // Subject assignment happens after admin approval.
                ];

                $this->teacherProfiles->update($user, $profileData);

                // Assign subject directly via the pivot, bypassing assertActiveTeacher
                if (($data['subject_mode'] ?? null) !== null) {
                    $this->assignSubjectOnRegistration($user, $data);
                }

                // Create the platform subscription in Trialing state.
                // The trial clock starts when the admin approves (approved_at is set).
                // Until approval, the teacher is in PendingAdmin and cannot access the platform
                // because EnsureAccountIsActive middleware blocks non-active users.
                app(\App\Modules\Payments\Services\PlatformBillingService::class)
                    ->ensureSubscription($user->fresh());
            }

            event(new Registered($user->fresh()));

            return $user->fresh();
        });
    }

    /**
     * Assign subject during registration — bypasses assertActiveTeacher
     * because the teacher is still PendingAdmin at this point.
     */
    private function assignSubjectOnRegistration(User $teacher, array $data): void
    {
        $mode = $data['subject_mode'] ?? null;

        if ($mode === 'catalog') {
            $subjectId = (int) ($data['subject_id'] ?? 0);
            $subject = Subject::query()
                ->whereKey($subjectId)
                ->where('is_active', true)
                ->where('is_custom', false)
                ->first();

            if ($subject) {
                $teacher->teachingSubjects()->sync([$subject->id]);
            }
        } elseif ($mode === 'custom') {
            $gradeId = (int) ($data['grade_id'] ?? 0);
            $name = trim((string) ($data['subject_name'] ?? ''));

            if ($name !== '' && $gradeId > 0) {
                $grade = Grade::query()->whereKey($gradeId)->where('is_active', true)->first();
                if ($grade) {
                    // Create custom subject directly, bypassing assertActiveTeacher
                    $subject = Subject::query()->create([
                        'grade_id'   => $grade->id,
                        'name'       => $name,
                        'code'       => Str::upper(Str::slug($name, '_')),
                        'ordering'   => ((int) $grade->subjects()->max('ordering') + 1),
                        'is_active'  => true,
                        'created_by' => $teacher->id,
                        'is_custom'  => true,
                    ]);

                    // Create default unit
                    Unit::query()->create([
                        'subject_id' => $subject->id,
                        'name'       => 'الوحدة الأولى',
                        'ordering'   => 1,
                        'is_active'  => true,
                    ]);

                    $teacher->teachingSubjects()->sync([$subject->id]);
                }
            }
        }
    }
}
