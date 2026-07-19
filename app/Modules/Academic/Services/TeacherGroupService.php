<?php

namespace App\Modules\Academic\Services;

use App\Enums\GroupMembershipStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeacherGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherGroupService
{
    /**
     * @param  array{name: string, schedule_note?: string|null, subject_id: int, is_active?: bool}  $data
     */
    public function create(User $teacher, array $data): TeacherGroup
    {
        $this->assertTeacher($teacher);

        $subject = $this->resolveTeacherSubject($teacher, (int) $data['subject_id']);

        return TeacherGroup::query()->create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'grade_id' => $subject->grade_id,
            'name' => trim($data['name']),
            'schedule_note' => filled($data['schedule_note'] ?? null) ? trim((string) $data['schedule_note']) : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array{name?: string, schedule_note?: string|null, is_active?: bool}  $data
     */
    public function update(User $teacher, TeacherGroup $group, array $data): TeacherGroup
    {
        $this->assertOwnsGroup($teacher, $group);

        if (array_key_exists('name', $data)) {
            $group->name = trim((string) $data['name']);
        }

        if (array_key_exists('schedule_note', $data)) {
            $group->schedule_note = filled($data['schedule_note']) ? trim((string) $data['schedule_note']) : null;
        }

        if (array_key_exists('is_active', $data)) {
            $group->is_active = (bool) $data['is_active'];
        }

        $group->save();

        return $group->refresh();
    }

    public function delete(User $teacher, TeacherGroup $group): void
    {
        $this->assertOwnsGroup($teacher, $group);
        $group->delete();
    }

    public function addStudent(
        User $teacher,
        TeacherGroup $group,
        User $student,
        GroupMembershipStatus $status = GroupMembershipStatus::Active,
    ): TeacherGroup {
        $this->assertOwnsGroup($teacher, $group);
        $this->assertLinkedStudent($teacher, $student);

        if (! $student->hasRole(UserRole::Student)) {
            throw ValidationException::withMessages([
                'student_id' => 'الحساب المحدد ليس طالبًا.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $group, $student, $status) {
            $teacher->students()->syncWithoutDetaching([
                $student->id => ['joined_at' => now()],
            ]);

            $existing = $group->students()->where('users.id', $student->id)->first();

            if ($existing) {
                $group->students()->updateExistingPivot($student->id, [
                    'status' => $status->value,
                    'left_at' => $status === GroupMembershipStatus::Active ? null : ($existing->pivot->left_at ?? now()),
                    'joined_at' => $existing->pivot->joined_at ?? now(),
                ]);
            } else {
                $group->students()->attach($student->id, [
                    'status' => $status->value,
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            }

            return $group->refresh();
        });
    }

    public function updateMembershipStatus(
        User $teacher,
        TeacherGroup $group,
        User $student,
        GroupMembershipStatus $status,
    ): void {
        $this->assertOwnsGroup($teacher, $group);

        $membership = $group->students()->where('users.id', $student->id)->first();
        if (! $membership) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب غير مسجّل في هذه المجموعة.',
            ]);
        }

        $group->students()->updateExistingPivot($student->id, [
            'status' => $status->value,
            'left_at' => $status === GroupMembershipStatus::Active ? null : now(),
        ]);
    }

    public function removeStudent(User $teacher, TeacherGroup $group, User $student): void
    {
        $this->assertOwnsGroup($teacher, $group);
        $group->students()->detach($student->id);
    }

    /**
     * @return Collection<int, TeacherGroup>
     */
    public function listForTeacher(User $teacher, ?int $gradeId = null): Collection
    {
        $this->assertTeacher($teacher);

        return TeacherGroup::query()
            ->with(['grade.stage', 'subject', 'students'])
            ->withCount([
                'students as active_students_count' => fn ($q) => $q
                    ->where('teacher_group_student.status', GroupMembershipStatus::Active->value),
            ])
            ->where('teacher_id', $teacher->id)
            ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get();
    }

    public function gradesAvailableForTeacher(User $teacher): Collection
    {
        $this->assertTeacher($teacher);

        $gradeIds = $teacher->teachingSubjects()->pluck('grade_id')->unique()->filter();

        return Grade::query()
            ->with('stage')
            ->whereIn('id', $gradeIds)
            ->where('is_active', true)
            ->orderBy('ordering')
            ->get();
    }

    private function resolveTeacherSubject(User $teacher, int $subjectId): Subject
    {
        $subject = $teacher->teachingSubjects()
            ->where('subjects.id', $subjectId)
            ->where('subjects.is_active', true)
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => 'اختر مادة تدرّسها لإنشاء المجموعة.',
            ]);
        }

        return $subject;
    }

    private function assertTeacher(User $teacher): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير مصرح بإدارة المجموعات.',
            ]);
        }
    }

    private function assertOwnsGroup(User $teacher, TeacherGroup $group): void
    {
        $this->assertTeacher($teacher);

        if ((int) $group->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'group' => 'لا يمكنك إدارة مجموعة مدرس آخر.',
            ]);
        }
    }

    private function assertLinkedStudent(User $teacher, User $student): void
    {
        $linked = $teacher->students()->where('users.id', $student->id)->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'student_id' => 'أضف الطالب لمكتبك أولًا قبل ضمه للمجموعة.',
            ]);
        }
    }
}
