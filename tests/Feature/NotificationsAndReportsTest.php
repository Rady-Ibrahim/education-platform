<?php

namespace Tests\Feature;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\QuestionType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Modules\Payments\Services\PaymentReviewService;
use App\Modules\Reports\Services\DashboardReportService;
use App\Notifications\ExamPublishedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentPendingReviewNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\SubscriptionExpiringNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class NotificationsAndReportsTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        config(['payments.student_vodafone_enabled' => true]);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_vodafone_proof_notifies_teacher_pending_review(): void
    {
        Notification::fake();

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-555'],
            \Illuminate\Http\UploadedFile::fake()->image('proof.jpg'),
        );

        Notification::assertSentTo($this->teacher, PaymentPendingReviewNotification::class);
    }

    public function test_confirming_payment_notifies_student(): void
    {
        Notification::fake();

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subscription_id' => $subscription->id,
            'channel' => PaymentChannel::VodafoneCash,
            'amount' => 100,
            'status' => PaymentStatus::PendingReview,
            'recorded_by' => $this->student->id,
        ]);

        app(PaymentReviewService::class)->confirm($this->teacher, $payment);

        Notification::assertSentTo($this->student, PaymentConfirmedNotification::class);
    }

    public function test_rejecting_payment_notifies_student(): void
    {
        Notification::fake();

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subscription_id' => $subscription->id,
            'channel' => PaymentChannel::VodafoneCash,
            'amount' => 100,
            'status' => PaymentStatus::PendingReview,
            'recorded_by' => $this->student->id,
        ]);

        app(PaymentReviewService::class)->reject($this->teacher, $payment, 'رقم خاطئ');

        Notification::assertSentTo($this->student, PaymentRejectedNotification::class);
    }

    public function test_publishing_exam_notifies_teacher_students(): void
    {
        Notification::fake();

        $question = app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => 'اختبار',
            'points' => 1,
            'options' => [
                ['label' => 'أ', 'is_correct' => true],
                ['label' => 'ب', 'is_correct' => false],
            ],
        ]);

        app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'امتحان إشعار',
            'question_ids' => [$question->id],
            'is_published' => true,
        ]);

        Notification::assertSentTo($this->student, ExamPublishedNotification::class);
    }

    public function test_expiring_subscription_reminder_is_sent(): void
    {
        Notification::fake();

        $plan = $this->createPlan($this->teacher, $this->subject);

        Subscription::query()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(27),
            'ends_at' => now()->addDays(2),
        ]);

        $sent = app(NotificationService::class)->notifyExpiringSubscriptions(3);

        $this->assertSame(1, $sent);
        Notification::assertSentTo($this->student, SubscriptionExpiringNotification::class);
    }

    public function test_admin_dashboard_kpis_are_correct(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'channel' => PaymentChannel::Cash,
            'amount' => 150,
            'status' => PaymentStatus::Confirmed,
            'recorded_by' => $this->teacher->id,
        ]);

        $stats = app(DashboardReportService::class)->forAdmin();

        $this->assertSame(1, $stats['active_subscriptions']);
        $this->assertEquals(150.0, $stats['confirmed_payments_total']);
    }

    public function test_teacher_dashboard_kpis_are_correct(): void
    {
        Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'channel' => PaymentChannel::Cash,
            'amount' => 200,
            'status' => PaymentStatus::Confirmed,
            'recorded_by' => $this->teacher->id,
        ]);

        $stats = app(DashboardReportService::class)->forTeacher($this->teacher);

        $this->assertSame(1, $stats['students_count']);
        $this->assertEquals(200.0, $stats['confirmed_total']);
    }

    public function test_student_dashboard_kpis_are_correct(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $stats = app(DashboardReportService::class)->forStudent($this->student);

        $this->assertSame(1, $stats['active_subscriptions']);
        $this->assertSame(0, $stats['pending_subscriptions']);
    }
}
