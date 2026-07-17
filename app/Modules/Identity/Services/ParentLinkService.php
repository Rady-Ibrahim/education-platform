<?php

namespace App\Modules\Identity\Services;

use App\Enums\ParentLinkStatus;
use App\Enums\ParentRelationship;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Models\ParentStudentLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentLinkService
{
    public function __construct(
        private readonly StudentCodeService $studentCodes,
    ) {}

    /**
     * ولي الأمر يطلب الربط بكود الطالب.
     */
    public function requestByStudentCode(
        User $parent,
        string $studentCode,
        ?string $relationship = null,
        ?string $message = null,
    ): ParentStudentLink {
        $this->assertActiveParent($parent);

        $student = $this->studentCodes->findActiveStudentByCode($studentCode);
        if (! $student) {
            throw ValidationException::withMessages([
                'student_code' => 'كود الطالب غير صحيح أو الحساب غير نشط.',
            ]);
        }

        return $this->linkByCodeImmediately($parent, $student, $relationship, $message);
    }

    /**
     * ربط ولي الأمر بكود الطالب يصبح نشطًا فورًا (بدون موافقة الطالب).
     * يمكن للطالب أو المدرس أو الأدمن إلغاء الربط لاحقًا.
     */
    private function linkByCodeImmediately(
        User $parent,
        User $student,
        ?string $relationship,
        ?string $message,
    ): ParentStudentLink {
        $existing = ParentStudentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing?->status === ParentLinkStatus::Active) {
            throw ValidationException::withMessages([
                'student_code' => 'هذا الابن مربوط بحسابك بالفعل.',
            ]);
        }

        return DB::transaction(function () use ($parent, $student, $relationship, $message, $existing) {
            $link = $existing ?? new ParentStudentLink([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
            ]);

            $link->fill([
                'status' => ParentLinkStatus::Active,
                'relationship' => $this->normalizeRelationship($relationship),
                'linked_by' => $parent->id,
                'approved_by' => $parent->id,
                'approved_at' => now(),
                'message' => $message,
            ])->save();

            return $link->refresh();
        });
    }

    /**
     * أدمن أو مدرس يربط ولي أمر بطالب مباشرة (نشط فورًا).
     */
    public function linkDirectly(User $actor, User $parent, User $student, ?string $relationship = null): ParentStudentLink
    {
        if (! $actor->hasAnyRole([UserRole::Admin, UserRole::Teacher]) || ! $actor->isActive()) {
            throw ValidationException::withMessages([
                'actor' => 'غير مصرح بربط ولي الأمر.',
            ]);
        }

        $this->assertActiveParent($parent);

        if (! $student->hasRole(UserRole::Student) || ! $student->isActive()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مؤهل للربط.',
            ]);
        }

        if ($actor->hasRole(UserRole::Teacher) && ! $actor->students()->where('users.id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب خارج نطاقك.',
            ]);
        }

        return DB::transaction(function () use ($actor, $parent, $student, $relationship) {
            $link = ParentStudentLink::query()->firstOrNew([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
            ]);

            $link->fill([
                'status' => ParentLinkStatus::Active,
                'relationship' => $this->normalizeRelationship($relationship),
                'linked_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'message' => null,
            ])->save();

            return $link->refresh();
        });
    }

    public function approveByStudent(ParentStudentLink $link, User $student): ParentStudentLink
    {
        if ($link->student_id !== $student->id || ! $student->hasRole(UserRole::Student)) {
            throw ValidationException::withMessages([
                'link' => 'غير مصرح بالموافقة على هذا الطلب.',
            ]);
        }

        if (! $link->isPending()) {
            throw ValidationException::withMessages([
                'link' => 'الطلب ليس بانتظار الموافقة.',
            ]);
        }

        $link->update([
            'status' => ParentLinkStatus::Active,
            'approved_by' => $student->id,
            'approved_at' => now(),
        ]);

        return $link->refresh();
    }

    public function rejectByStudent(ParentStudentLink $link, User $student): ParentStudentLink
    {
        if ($link->student_id !== $student->id || ! $student->hasRole(UserRole::Student)) {
            throw ValidationException::withMessages([
                'link' => 'غير مصرح برفض هذا الطلب.',
            ]);
        }

        if (! $link->isPending()) {
            throw ValidationException::withMessages([
                'link' => 'الطلب ليس بانتظار الموافقة.',
            ]);
        }

        $link->update([
            'status' => ParentLinkStatus::Rejected,
            'approved_by' => $student->id,
            'approved_at' => now(),
        ]);

        return $link->refresh();
    }

    public function revoke(User $actor, ParentStudentLink $link): ParentStudentLink
    {
        $allowed = $actor->hasRole(UserRole::Admin)
            || ($actor->hasRole(UserRole::Parent) && $link->parent_id === $actor->id)
            || ($actor->hasRole(UserRole::Student) && $link->student_id === $actor->id);

        if (! $allowed) {
            throw ValidationException::withMessages([
                'link' => 'غير مصرح بإلغاء الربط.',
            ]);
        }

        $link->update([
            'status' => ParentLinkStatus::Revoked,
        ]);

        return $link->refresh();
    }

    /**
     * @return list<User>
     */
    public function activeChildren(User $parent): array
    {
        return $parent->children()
            ->wherePivot('status', ParentLinkStatus::Active->value)
            ->get()
            ->all();
    }

    public function parentCanViewStudent(User $parent, User $student): bool
    {
        if (! $parent->hasRole(UserRole::Parent) || ! $parent->isActive()) {
            return false;
        }

        return ParentStudentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', ParentLinkStatus::Active)
            ->exists();
    }

    private function assertActiveParent(User $parent): void
    {
        if (! $parent->hasRole(UserRole::Parent) || ! $parent->isActive()) {
            throw ValidationException::withMessages([
                'parent' => 'حساب ولي الأمر غير نشط.',
            ]);
        }
    }

    private function normalizeRelationship(?string $relationship): ?ParentRelationship
    {
        if ($relationship === null || $relationship === '') {
            return null;
        }

        return ParentRelationship::from($relationship);
    }
}
