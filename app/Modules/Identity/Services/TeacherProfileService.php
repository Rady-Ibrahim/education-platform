<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherProfileService
{
    public function __construct(
        private readonly AcademicStructureService $academic,
    ) {}

    /**
     * @param  array{
     *     name?: string,
     *     phone?: string|null,
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
    public function update(User $teacher, array $data): User
    {
        if (! $teacher->hasRole(UserRole::Teacher)) {
            throw ValidationException::withMessages([
                'teacher' => 'الحساب ليس مدرسًا.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $data) {
            if (isset($data['name']) && $data['name'] !== $teacher->name) {
                $teacher->name = $data['name'];
                if (! $teacher->slug) {
                    $teacher->slug = $this->uniqueSlug($data['name'], $teacher->id);
                }
            }

            if (array_key_exists('phone', $data)) {
                $teacher->phone = ($data['phone'] ?? null) ?: null;
            }

            if (array_key_exists('headline', $data)) {
                $teacher->headline = ($data['headline'] ?? null) ?: null;
            }

            if (array_key_exists('bio', $data)) {
                $teacher->bio = ($data['bio'] ?? null) ?: null;
            }

            if (array_key_exists('vodafone_cash_number', $data)) {
                $teacher->vodafone_cash_number = ($data['vodafone_cash_number'] ?? null) ?: null;
            }

            if (array_key_exists('payment_instructions', $data)) {
                $teacher->payment_instructions = ($data['payment_instructions'] ?? null) ?: null;
            }

            if (array_key_exists('is_publicly_visible', $data)) {
                $teacher->is_publicly_visible = (bool) $data['is_publicly_visible'];
            }

            if (! $teacher->slug) {
                $teacher->slug = $this->uniqueSlug($teacher->name, $teacher->id);
            }

            $teacher->save();

            if (($data['subject_mode'] ?? null) !== null) {
                $this->setSingleSubject($teacher, $data);
            }

            if ($teacher->fresh()->is_publicly_visible && ! $this->canBePublic($teacher->fresh())) {
                throw ValidationException::withMessages([
                    'is_publicly_visible' => 'لإظهار صفحتك للعامة أضف نبذة قصيرة ورقم فودافون كاش ومادتك.',
                ]);
            }

            return $teacher->refresh();
        });
    }

    /**
     * كل مدرس له مادة واحدة فقط: اختيار من الكتالوج أو كتابة اسم مادة جديدة.
     *
     * @param  array{
     *     subject_mode: 'catalog'|'custom',
     *     subject_id?: int|null,
     *     grade_id?: int|null,
     *     subject_name?: string|null
     * }  $data
     */
    public function setSingleSubject(User $teacher, array $data): Subject
    {
        $mode = $data['subject_mode'] ?? null;

        if ($mode === 'catalog') {
            $subjectId = (int) ($data['subject_id'] ?? 0);
            $subject = Subject::query()
                ->whereKey($subjectId)
                ->where('is_active', true)
                ->where('is_custom', false)
                ->first();

            if (! $subject) {
                throw ValidationException::withMessages([
                    'subject_id' => 'اختر مادة من كتالوج السنتر.',
                ]);
            }

            $teacher->teachingSubjects()->sync([$subject->id]);

            return $subject;
        }

        if ($mode === 'custom') {
            $gradeId = (int) ($data['grade_id'] ?? 0);
            $name = trim((string) ($data['subject_name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'subject_name' => 'اكتب اسم مادتك.',
                ]);
            }

            $grade = Grade::query()->whereKey($gradeId)->where('is_active', true)->first();
            if (! $grade) {
                throw ValidationException::withMessages([
                    'grade_id' => 'اختر الصف لمادتك.',
                ]);
            }

            $existing = $teacher->teachingSubjects()->first();

            if ($existing && $existing->is_custom && (int) $existing->created_by === (int) $teacher->id) {
                $existing->update([
                    'name' => $name,
                    'grade_id' => $grade->id,
                ]);
                $teacher->teachingSubjects()->sync([$existing->id]);

                return $existing->refresh();
            }

            $subject = $this->academic->createSubjectForTeacher($teacher, $grade, [
                'name' => $name,
            ]);

            // createSubjectForTeacher already assigns; enforce single subject
            $teacher->teachingSubjects()->sync([$subject->id]);

            return $subject;
        }

        throw ValidationException::withMessages([
            'subject_mode' => 'حدّد مادتك: من الكتالوج أو اكتب اسمها.',
        ]);
    }

    public function ensureSlug(User $teacher): string
    {
        if ($teacher->slug) {
            return $teacher->slug;
        }

        $slug = $this->uniqueSlug($teacher->name, $teacher->id);
        $teacher->forceFill(['slug' => $slug])->save();

        return $slug;
    }

    public function uniqueSlug(string $name, ?int $ignoreUserId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'teacher';
        }

        $slug = $base;
        $i = 2;

        while (
            User::query()
                ->where('slug', $slug)
                ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function canBePublic(User $teacher): bool
    {
        $hasHeadlineOrBio = filled($teacher->headline) || filled($teacher->bio);
        $hasWallet = filled($teacher->vodafone_cash_number);
        $hasSubject = $teacher->teachingSubjects()->exists();

        return $hasHeadlineOrBio && $hasWallet && $hasSubject;
    }
}
