<?php

namespace Tests\Feature;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Modules\Payments\Services\PaymentReviewService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class PaymentsModuleTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $otherTeacher;

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

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        $academic = app(AcademicStructureService::class);
        $academic->assignTeacherToSubject($this->teacher, $this->subject);
        $academic->assignTeacherToSubject($this->otherTeacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_teacher_can_create_subscription_plan(): void
    {
        $plan = app(SubscriptionPlanService::class)->create($this->teacher, $this->subject, [
            'name' => 'شهري',
            'price' => 250,
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'price' => 250,
        ]);
    }

    public function test_student_enrollment_creates_pending_payment_subscription(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);

        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $this->assertSame(SubscriptionStatus::PendingPayment, $subscription->status);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_teacher_cash_payment_activates_subscription_and_issues_invoice(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudentByTeacher($this->teacher, $this->student, $plan);

        $payment = app(PaymentRecordService::class)->recordCash($this->teacher, $this->student, $subscription, []);

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(PaymentChannel::Cash, $payment->channel);
        $this->assertTrue($subscription->fresh()->isActive());
        $this->assertDatabaseHas('invoices', [
            'payment_id' => $payment->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_student_vodafone_proof_stays_pending_until_teacher_confirms(): void
    {
        Storage::fake('public');

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-12345'],
            UploadedFile::fake()->image('proof.jpg'),
        );

        $this->assertSame(PaymentStatus::PendingReview, $payment->status);
        $this->assertFalse($subscription->fresh()->isActive());

        app(PaymentReviewService::class)->confirm($this->teacher, $payment);

        $this->assertTrue($subscription->fresh()->isActive());
        $this->assertDatabaseHas('invoices', ['payment_id' => $payment->id]);
    }

    public function test_teacher_can_reject_vodafone_payment(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-99999'],
        );

        app(PaymentReviewService::class)->reject($this->teacher, $payment, 'رقم العملية غير صحيح');

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame(SubscriptionStatus::PendingPayment, $subscription->fresh()->status);
    }

    public function test_teacher_cannot_review_other_teacher_payment(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-00001'],
        );

        $this->expectException(ValidationException::class);
        app(PaymentReviewService::class)->confirm($this->otherTeacher, $payment);
    }

    public function test_content_access_requires_active_subscription(): void
    {
        $access = app(ContentAccessService::class);

        $this->assertTrue($access->studentIsLinkedToSubjectTeacher($this->student, $this->subject));
        $this->assertFalse($access->studentCanAccessSubject($this->student, $this->subject));

        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $this->assertTrue($access->studentCanAccessSubject($this->student, $this->subject));
    }

    public function test_admin_can_confirm_any_pending_payment(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

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

        app(PaymentReviewService::class)->confirm($admin, $payment);

        $this->assertTrue($subscription->fresh()->isActive());
    }
}
