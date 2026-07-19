<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Academic\Services\TeacherGroupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherStudentService
{
    public function __construct(
        private readonly AcademicStructureService $academic,
        private readonly TeacherGroupService $groups,
    ) {}

    /**
     * المدرس يضيف طالبًا يدويًا → الحساب نشط مباشرة (بدون انتظار الأدمن).
     *
     * @param  array{name: string, email: string, phone?: string|null, password?: string|null, grade_id: int, group_id?: int|null}  $data
     * @return array{user: User, plain_password: string}
     */
    public function createStudent(User $teacher, array $data): array
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير مصرح بإضافة طلاب.',
            ]);
        }

        $grade = Grade::query()->whereKey((int) ($data['grade_id'] ?? 0))->where('is_active', true)->first();
        if (! $grade) {
            throw ValidationException::withMessages([
                'grade_id' => 'اختر الصف الدراسي للطالب.',
            ]);
        }

        $group = null;
        if (! empty($data['group_id'])) {
            $group = TeacherGroup::query()
                ->whereKey((int) $data['group_id'])
                ->where('teacher_id', $teacher->id)
                ->where('grade_id', $grade->id)
                ->first();

            if (! $group) {
                throw ValidationException::withMessages([
                    'group_id' => 'اختر مجموعة تابعة لنفس الصف الدراسي.',
                ]);
            }
        }

        return DB::transaction(function () use ($teacher, $data, $grade, $group) {
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
                'student_code' => app(StudentCodeService::class)->generate(),
                'email_verified_at' => now(),
            ]);

            $student->assignRole(UserRole::Student);

            $this->academic->enrollStudentInGrade($student, $grade);

            $teacher->students()->syncWithoutDetaching([
                $student->id => ['joined_at' => now()],
            ]);

            if ($group) {
                $this->groups->addStudent($teacher, $group, $student);
            }

            return [
                'user' => $student->refresh(),
                'plain_password' => $plainPassword,
            ];
        });
    }
}
