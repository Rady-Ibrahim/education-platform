<?php

namespace App\Modules\Reports\Services;

use App\Enums\AttendanceStatus;
use App\Enums\GroupMembershipStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\ParentLinkStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\GroupAttendanceRecord;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Services\GroupAttendanceService;
use App\Modules\Content\Models\LessonProgress;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Models\TeacherParentMessage;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Services\MonthlyCollectionService;

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
     *     groups_count: int,
     *     confirmed_total: float,
     *     pending_payments: int,
     *     late_subscriptions: int,
     *     pending_join_requests: int,
     *     average_exam_score: float|null,
     *     attention_count: int,
     *     todays_absent: int,
     *     groups: list<array{id: int, name: string, grade: string|null, schedule_note: string|null, active_students: int}>
     * }
     */
    public function forTeacher(User $teacher): array
    {
        $studentIds = $teacher->students()->pluck('users.id');

        $pendingPayments = Payment::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::PendingReview)
            ->count();

        $lateSubscriptions = Subscription::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::PendingPayment)
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        $pendingJoin = TeacherJoinRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', JoinRequestStatus::Pending)
            ->count();

        $todaysAbsent = app(GroupAttendanceService::class)->todaysAbsentCount($teacher);

        $groups = TeacherGroup::query()
            ->with('grade:id,name')
            ->withCount([
                'students as active_students_count' => fn ($q) => $q
                    ->where('teacher_group_student.status', GroupMembershipStatus::Active->value),
            ])
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->orderBy('grade_id')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (TeacherGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'grade' => $group->grade?->name,
                'schedule_note' => $group->schedule_note,
                'active_students' => (int) $group->active_students_count,
            ])
            ->all();

        return [
            'students_count' => $studentIds->count(),
            'groups_count' => TeacherGroup::query()->where('teacher_id', $teacher->id)->count(),
            'confirmed_total' => (float) Payment::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', PaymentStatus::Confirmed)
                ->sum('amount'),
            'pending_payments' => $pendingPayments,
            'late_subscriptions' => $lateSubscriptions,
            'pending_join_requests' => $pendingJoin,
            'average_exam_score' => $this->averageAttemptPercentForTeacher($teacher),
            'todays_absent' => $todaysAbsent,
            'owing_this_month' => app(MonthlyCollectionService::class)->owingCountForMonth($teacher),
            'owing_total_this_month' => app(MonthlyCollectionService::class)->owingTotalForMonth($teacher),
            'attention_count' => $pendingPayments + $pendingJoin + $lateSubscriptions + $todaysAbsent
                + app(MonthlyCollectionService::class)->owingCountForMonth($teacher),
            'groups' => $groups,
        ];
    }

    /**
     * @return array{
     *     active_subscriptions: int,
     *     pending_subscriptions: int,
     *     completed_lessons: int,
     *     unread_notifications: int,
     *     teachers_count: int,
     *     groups_count: int
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
            'teachers_count' => $student->teachers()->count(),
            'groups_count' => $student->studentGroups()
                ->wherePivot('status', GroupMembershipStatus::Active->value)
                ->count(),
        ];
    }

    /**
     * @return array{
     *     unread_notifications: int,
     *     unread_messages: int,
     *     children_count: int,
     *     attention_count: int,
     *     children: list<array{
     *         id: int,
     *         name: string,
     *         student_code: string|null,
     *         active_subscriptions: int,
     *         pending_subscriptions: int,
     *         completed_lessons: int,
     *         average_exam_score: float|null,
     *         pending_payments: int,
     *         groups: list<string>
     *     }>
     * }
     */
    public function forParent(User $parent): array
    {
        $children = $parent->children()
            ->wherePivot('status', ParentLinkStatus::Active->value)
            ->get();

        $childStats = [];
        $attention = 0;

        foreach ($children as $child) {
            $studentStats = $this->forStudent($child);
            $pendingPayments = Payment::query()
                ->where('student_id', $child->id)
                ->where('status', PaymentStatus::PendingReview)
                ->count();

            $recentAbsences = $this->recentAbsencesForStudent($child, 7);
            $attention += $studentStats['pending_subscriptions'] + $pendingPayments + count($recentAbsences);

            $groups = $child->studentGroups()
                ->wherePivot('status', GroupMembershipStatus::Active->value)
                ->with('grade:id,name')
                ->get()
                ->map(fn (TeacherGroup $g) => trim(($g->grade?->name ? $g->grade->name.' / ' : '').$g->name))
                ->all();

            $childStats[] = [
                'id' => $child->id,
                'name' => $child->name,
                'student_code' => $child->student_code,
                'active_subscriptions' => $studentStats['active_subscriptions'],
                'pending_subscriptions' => $studentStats['pending_subscriptions'],
                'completed_lessons' => $studentStats['completed_lessons'],
                'average_exam_score' => $this->averageAttemptPercentForStudent($child),
                'pending_payments' => $pendingPayments,
                'groups' => $groups,
                'recent_absences' => $recentAbsences,
            ];
        }

        $unreadMessages = TeacherParentMessage::query()
            ->where('parent_id', $parent->id)
            ->whereNull('read_at')
            ->count();

        return [
            'unread_notifications' => $parent->unreadNotifications()->count(),
            'unread_messages' => $unreadMessages,
            'children_count' => $children->count(),
            'attention_count' => $attention + $unreadMessages,
            'children' => $childStats,
        ];
    }

    /**
     * @return list<array{date: string, group: string, status: string}>
     */
    private function recentAbsencesForStudent(User $student, int $days = 7): array
    {
        return GroupAttendanceRecord::query()
            ->with(['session.group:id,name'])
            ->where('student_id', $student->id)
            ->whereIn('status', [AttendanceStatus::Absent->value, AttendanceStatus::Excused->value, AttendanceStatus::Late->value])
            ->whereHas('session', fn ($q) => $q->whereDate('session_date', '>=', now()->subDays($days)->toDateString()))
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (GroupAttendanceRecord $record) => [
                'date' => $record->session?->session_date?->toDateString() ?? '—',
                'group' => $record->session?->group?->name ?? '—',
                'status' => $record->status->label(),
            ])
            ->all();
    }

    private function averageAttemptPercentForStudent(User $student): ?float
    {
        $avg = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->selectRaw('AVG((score / max_score) * 100) as avg_percent')
            ->value('avg_percent');

        return $avg === null ? null : round((float) $avg, 1);
    }

    private function averageAttemptPercent(): ?float
    {
        $avg = ExamAttempt::query()
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->selectRaw('AVG((score / max_score) * 100) as avg_percent')
            ->value('avg_percent');

        return $avg === null ? null : round((float) $avg, 1);
    }

    private function averageAttemptPercentForTeacher(User $teacher): ?float
    {
        $avg = ExamAttempt::query()
            ->whereHas('exam', fn ($q) => $q->where('created_by', $teacher->id))
            ->whereNotNull('max_score')
            ->where('max_score', '>', 0)
            ->whereNotNull('score')
            ->selectRaw('AVG((score / max_score) * 100) as avg_percent')
            ->value('avg_percent');

        return $avg === null ? null : round((float) $avg, 1);
    }
}
