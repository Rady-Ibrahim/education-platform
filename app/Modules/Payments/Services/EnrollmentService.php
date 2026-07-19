<?php

namespace App\Modules\Payments\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    public function enrollStudent(User $student, SubscriptionPlan $plan): Subscription
    {
        if (! $student->hasRole(UserRole::Student) || ! $student->isActive()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مؤهل للاشتراك.',
            ]);
        }

        $plan->loadMissing('subject', 'teacher');
        $teacher = $plan->teacher;

        if (! $teacher) {
            throw ValidationException::withMessages([
                'plan' => 'الخطة غير مرتبطة بمدرس.',
            ]);
        }

        if (! $this->access->studentIsLinkedToSubjectTeacher($student, $plan->subject)) {
            throw ValidationException::withMessages([
                'student' => 'يجب أن تكون منضمًا للمدرس أولاً.',
            ]);
        }

        $existingActive = Subscription::query()
            ->where('student_id', $student->id)
            ->where('subject_id', $plan->subject_id)
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::Active)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        if ($existingActive) {
            throw ValidationException::withMessages([
                'subscription' => 'لديك اشتراك نشط على هذه المادة.',
            ]);
        }

        $subscription = Subscription::query()->create([
            'student_id' => $student->id,
            'subject_id' => $plan->subject_id,
            'teacher_id' => $teacher->id,
            'plan_id' => $plan->id,
            'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
            'status' => SubscriptionStatus::PendingPayment,
        ]);

        app(MonthlyCollectionService::class)->ensureChargeForSubscription($subscription);

        return $subscription->fresh();
    }

    public function activate(Subscription $subscription): Subscription
    {
        $plan = $subscription->plan;
        $startsAt = now();
        $endsAt = $startsAt->copy()->addDays($plan->duration_days);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return $subscription->refresh();
    }

    public function suspend(User $teacher, Subscription $subscription): Subscription
    {
        $this->assertTeacherOwnsSubscription($teacher, $subscription);

        if ($subscription->status !== SubscriptionStatus::Active) {
            throw ValidationException::withMessages([
                'subscription' => 'يمكن إيقاف الاشتراكات النشطة فقط.',
            ]);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Suspended,
        ]);

        return $subscription->refresh();
    }

    public function reactivate(User $teacher, Subscription $subscription): Subscription
    {
        $this->assertTeacherOwnsSubscription($teacher, $subscription);

        if ($subscription->status !== SubscriptionStatus::Suspended) {
            throw ValidationException::withMessages([
                'subscription' => 'يمكن إعادة تفعيل الاشتراكات الموقوفة فقط.',
            ]);
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            throw ValidationException::withMessages([
                'subscription' => 'انتهت مدة الاشتراك. سجّل الطالب على خطة جديدة.',
            ]);
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
        ]);

        return $subscription->refresh();
    }

    private function assertTeacherOwnsSubscription(User $teacher, Subscription $subscription): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'غير مصرح.',
            ]);
        }

        if ($subscription->teacher_id !== $teacher->id) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك خارج نطاقك.',
            ]);
        }
    }

    /**
     * تسجيل طالب على خطة من مكتب المدرس.
     */
    public function enrollStudentByTeacher(User $teacher, User $student, SubscriptionPlan $plan): Subscription
    {
        $plan->loadMissing('subject', 'teacher');
        $this->access->assertTeacherOwnsSubject($teacher, $plan->subject);

        if ($plan->teacher_id && $plan->teacher_id !== $teacher->id) {
            throw ValidationException::withMessages([
                'plan' => 'الخطة خارج نطاقك.',
            ]);
        }

        if (! $teacher->students()->where('users.id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مرتبط بك.',
            ]);
        }

        return $this->enrollStudent($student, $plan);
    }

    public function studentHasActiveSubscription(User $student, int $subjectId, ?int $teacherId = null): bool
    {
        return $this->access->studentHasActiveSubscription($student, $subjectId, $teacherId);
    }
}
