<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherProfileService
{
    /**
     * @param  array{
     *     name?: string,
     *     phone?: string|null,
     *     headline?: string|null,
     *     bio?: string|null,
     *     vodafone_cash_number?: string|null,
     *     payment_instructions?: string|null,
     *     is_publicly_visible?: bool,
     *     subject_ids?: list<int>
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

            if ($teacher->is_publicly_visible && ! $this->canBePublic($teacher, $data['subject_ids'] ?? null)) {
                throw ValidationException::withMessages([
                    'is_publicly_visible' => 'لإظهار صفحتك للعامة أضف نبذة قصيرة ورقم فودافون كاش ومادة واحدة على الأقل.',
                ]);
            }

            $teacher->save();

            if (array_key_exists('subject_ids', $data)) {
                $this->syncSubjects($teacher, $data['subject_ids'] ?? []);
            }

            return $teacher->refresh();
        });
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

    /**
     * @param  list<int>  $subjectIds
     */
    public function syncSubjects(User $teacher, array $subjectIds): void
    {
        $ids = collect($subjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            $validCount = Subject::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->count();

            if ($validCount !== count($ids)) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'بعض المواد غير صالحة.',
                ]);
            }
        }

        $teacher->teachingSubjects()->sync($ids);
    }

    /**
     * @param  list<int>|null  $incomingSubjectIds
     */
    private function canBePublic(User $teacher, ?array $incomingSubjectIds): bool
    {
        $hasHeadlineOrBio = filled($teacher->headline) || filled($teacher->bio);
        $hasWallet = filled($teacher->vodafone_cash_number);
        $subjectCount = $incomingSubjectIds !== null
            ? count(array_filter($incomingSubjectIds))
            : $teacher->teachingSubjects()->count();

        return $hasHeadlineOrBio && $hasWallet && $subjectCount > 0;
    }
}
