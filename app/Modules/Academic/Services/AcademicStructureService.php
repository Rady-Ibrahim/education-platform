<?php

namespace App\Modules\Academic\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicStructureService
{
    public function createStage(array $data): Stage
    {
        $code = $data['code'] ?? $this->uniqueCode(Stage::class, Str::upper(Str::slug($data['name'], '_')));

        return Stage::query()->create([
            'name' => $data['name'],
            'code' => $code,
            'ordering' => $data['ordering'] ?? ((int) Stage::query()->max('ordering') + 1),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateStage(Stage $stage, array $data): Stage
    {
        $stage->update([
            'name' => $data['name'] ?? $stage->name,
            'code' => $data['code'] ?? $stage->code,
            'ordering' => $data['ordering'] ?? $stage->ordering,
            'is_active' => $data['is_active'] ?? $stage->is_active,
        ]);

        return $stage->refresh();
    }

    public function createGrade(Stage $stage, array $data): Grade
    {
        $base = $data['code'] ?? Str::upper(Str::slug($data['name'], '_'));
        $code = $this->uniqueScopedCode(Grade::class, 'stage_id', $stage->id, $base);

        return Grade::query()->create([
            'stage_id' => $stage->id,
            'name' => $data['name'],
            'code' => $code,
            'ordering' => $data['ordering'] ?? ((int) $stage->grades()->max('ordering') + 1),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateGrade(Grade $grade, array $data): Grade
    {
        $grade->update([
            'name' => $data['name'] ?? $grade->name,
            'code' => $data['code'] ?? $grade->code,
            'ordering' => $data['ordering'] ?? $grade->ordering,
            'is_active' => $data['is_active'] ?? $grade->is_active,
        ]);

        return $grade->refresh();
    }

    public function createSubject(Grade $grade, array $data): Subject
    {
        $base = $data['code'] ?? Str::upper(Str::slug($data['name'], '_'));
        $code = $this->uniqueScopedCode(Subject::class, 'grade_id', $grade->id, $base);

        return Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => $data['name'],
            'code' => $code,
            'description' => $data['description'] ?? null,
            'ordering' => $data['ordering'] ?? ((int) $grade->subjects()->max('ordering') + 1),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateSubject(Subject $subject, array $data): Subject
    {
        $subject->update([
            'name' => $data['name'] ?? $subject->name,
            'code' => $data['code'] ?? $subject->code,
            'description' => array_key_exists('description', $data) ? $data['description'] : $subject->description,
            'ordering' => $data['ordering'] ?? $subject->ordering,
            'is_active' => $data['is_active'] ?? $subject->is_active,
        ]);

        return $subject->refresh();
    }

    public function createUnit(Subject $subject, array $data): Unit
    {
        return Unit::query()->create([
            'subject_id' => $subject->id,
            'name' => $data['name'],
            'ordering' => $data['ordering'] ?? ((int) $subject->units()->max('ordering') + 1),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateUnit(Unit $unit, array $data): Unit
    {
        $unit->update([
            'name' => $data['name'] ?? $unit->name,
            'ordering' => $data['ordering'] ?? $unit->ordering,
            'is_active' => $data['is_active'] ?? $unit->is_active,
        ]);

        return $unit->refresh();
    }

    public function assignTeacherToSubject(User $teacher, Subject $subject): void
    {
        $this->assertActiveTeacher($teacher);

        if (! $subject->is_active) {
            throw ValidationException::withMessages([
                'subject' => 'المادة غير نشطة.',
            ]);
        }

        $subject->teachers()->syncWithoutDetaching([$teacher->id]);
    }

    public function detachTeacherFromSubject(User $teacher, Subject $subject): void
    {
        $subject->teachers()->detach($teacher->id);
    }

    public function enrollStudentInGrade(User $student, Grade $grade): void
    {
        if (! $student->hasRole(UserRole::Student) || ! $student->isActive()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مؤهل للتسجيل في صف.',
            ]);
        }

        if (! $grade->is_active) {
            throw ValidationException::withMessages([
                'grade' => 'الصف غير نشط.',
            ]);
        }

        DB::transaction(function () use ($student, $grade) {
            $student->grades()->sync([
                $grade->id => ['enrolled_at' => now()],
            ]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Subject>
     */
    public function subjectsForTeacher(User $teacher)
    {
        $this->assertActiveTeacher($teacher);

        return Subject::query()
            ->with(['grade.stage', 'units'])
            ->whereHas('teachers', fn ($q) => $q->where('users.id', $teacher->id))
            ->where('is_active', true)
            ->orderBy('ordering')
            ->get();
    }

    private function assertActiveTeacher(User $teacher): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || $teacher->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير مصرح.',
            ]);
        }
    }

    private function uniqueCode(string $modelClass, string $base): string
    {
        $code = $base !== '' ? $base : 'ITEM';
        $i = 1;

        while ($modelClass::query()->where('code', $code)->exists()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }

    private function uniqueScopedCode(string $modelClass, string $scopeColumn, int $scopeId, string $base): string
    {
        $code = $base !== '' ? $base : 'ITEM';
        $i = 1;

        while ($modelClass::query()->where($scopeColumn, $scopeId)->where('code', $code)->exists()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }
}
