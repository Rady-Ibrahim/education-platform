<?php

namespace App\Modules\Reports\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Content\Models\LessonProgress;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;

class DashboardReportService
{
    /**
     * @return array{
     *     public_teachers: int,
     *     suspended_users: int,
     *     active_subscriptions: int,
     *     pending_payments: int,
     *     confirmed_payments_total: float,
     *     completed_lessons: int,
     *     average_exam_score: float|null
     * }
     */
    public function forAdmin(): array
    {
        return [
            'public_teachers' => User::query()
                ->role('teacher')
                ->where('status', UserStatus::Active)
                ->where('is_publicly_visible', true)
                ->count(),
            'suspended_users' => User::query()->where('status', UserStatus::Suspended)->count(),
            'active_subscriptions' => Subscription::query()
                ->where('status', SubscriptionStatus::Active)
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->count(),
            'pending_payments' => Payment::query()->where('status', PaymentStatus::PendingReview)->count(),
            'confirmed_payments_total' => (float) Payment::query()
                ->where('status', PaymentStatus::Confirmed)
                ->sum('amount'),
            'completed_lessons' => LessonProgress::query()->where('is_completed', true)->count(),
            'average_exam_score' => $this->averageAttemptPercent(),
        ];
    }

    /**
     * @return array{
     *     students_count: int,
     *     confirmed_total: float,
     *     pending_payments: int,
     *     late_subscriptions: int,
     *     average_exam_score: float|null
     * }
     */
    public function forTeacher(User $teacher): array
    {
        $studentIds = $teacher->students()->pluck('users.id');

        return [
            'students_count' => $studentIds->count(),
            'confirmed_total' => (float) Payment::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', PaymentStatus::Confirmed)
                ->sum('amount'),
            'pending_payments' => Payment::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', PaymentStatus::PendingReview)
                ->count(),
            'late_subscriptions' => Subscription::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', SubscriptionStatus::PendingPayment)
                ->where('created_at', '<', now()->subDays(3))
                ->count(),
            'average_exam_score' => $this->averageAttemptPercentForTeacher($teacher),
        ];
    }

    /**
     * @return array{
     *     active_subscriptions: int,
     *     pending_subscriptions: int,
     *     completed_lessons: int,
     *     unread_notifications: int
     * }
     */
    public function forStudent(User $student): array
    {
        return [
            'active_subscriptions' => Subscription::query()
                ->where('student_id', $student->id)
                ->where('status', SubscriptionStatus::Active)
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->count(),
            'pending_subscriptions' => Subscription::query()
                ->where('student_id', $student->id)
                ->where('status', SubscriptionStatus::PendingPayment)
                ->count(),
            'completed_lessons' => LessonProgress::query()
                ->where('student_id', $student->id)
                ->where('is_completed', true)
                ->count(),
            'unread_notifications' => $student->unreadNotifications()->count(),
        ];
    }

    /**
     * @return array{
     *     unread_notifications: int,
     *     children_count: int,
     *     children: list<array{
     *         id: int,
     *         name: string,
     *         student_code: string|null,
     *         active_subscriptions: int,
     *         pending_subscriptions: int,
     *         completed_lessons: int,
     *         average_exam_score: float|null,
     *         pending_payments: int
     *     }>
     * }
     */
    public function forParent(User $parent): array
    {
        $children = $parent->children()
            ->wherePivot('status', \App\Enums\ParentLinkStatus::Active->value)
            ->get();

        $childStats = [];

        foreach ($children as $child) {
            $studentStats = $this->forStudent($child);
            $childStats[] = [
                'id' => $child->id,
                'name' => $child->name,
                'student_code' => $child->student_code,
                'active_subscriptions' => $studentStats['active_subscriptions'],
                'pending_subscriptions' => $studentStats['pending_subscriptions'],
                'completed_lessons' => $studentStats['completed_lessons'],
                'average_exam_score' => $this->averageAttemptPercentForStudent($child),
                'pending_payments' => Payment::query()
                    ->where('student_id', $child->id)
                    ->where('status', PaymentStatus::PendingReview)
                    ->count(),
            ];
        }

        return [
            'unread_notifications' => $parent->unreadNotifications()->count(),
            'children_count' => $children->count(),
            'children' => $childStats,
        ];
    }

    private function averageAttemptPercentForStudent(User $student): ?float
    {
        $attempts = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->get(['score', 'max_score']);

        if ($attempts->isEmpty()) {
            return null;
        }

        $avg = $attempts->avg(fn (ExamAttempt $a) => ((float) $a->score / (float) $a->max_score) * 100);

        return round((float) $avg, 1);
    }

    private function averageAttemptPercent(): ?float
    {
        $attempts = ExamAttempt::query()
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->get(['score', 'max_score']);

        if ($attempts->isEmpty()) {
            return null;
        }

        $avg = $attempts->avg(fn (ExamAttempt $a) => ((float) $a->score / (float) $a->max_score) * 100);

        return round((float) $avg, 1);
    }

    private function averageAttemptPercentForTeacher(User $teacher): ?float
    {
        $attempts = ExamAttempt::query()
            ->whereHas('exam', fn ($q) => $q->where('created_by', $teacher->id))
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->get(['score', 'max_score']);

        if ($attempts->isEmpty()) {
            return null;
        }

        $avg = $attempts->avg(fn (ExamAttempt $a) => ((float) $a->score / (float) $a->max_score) * 100);

        return round((float) $avg, 1);
    }
}
