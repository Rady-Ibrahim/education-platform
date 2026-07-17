<?php

namespace Tests\Unit\Modules\Payments;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentReviewService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class PaymentReviewServiceTest extends TestCase
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
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_cannot_confirm_already_confirmed_payment(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'subscription_id' => $subscription->id,
            'channel' => PaymentChannel::Cash,
            'amount' => 100,
            'status' => PaymentStatus::Confirmed,
            'recorded_by' => $this->teacher->id,
            'reviewed_by' => $this->teacher->id,
            'reviewed_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(PaymentReviewService::class)->confirm($this->teacher, $payment);
    }

    public function test_student_cannot_review_payment(): void
    {
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

        $this->expectException(ValidationException::class);
        app(PaymentReviewService::class)->confirm($this->student, $payment);
    }

    public function test_other_teacher_cannot_confirm(): void
    {
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

        $this->expectException(ValidationException::class);
        app(PaymentReviewService::class)->confirm($this->otherTeacher, $payment);
    }
}
