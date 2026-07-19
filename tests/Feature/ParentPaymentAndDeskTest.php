<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ParentRelationship;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Identity\Services\StudentCodeService;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Modules\Payments\Services\PaymentReviewService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class ParentPaymentAndDeskTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private User $parent;

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

        $this->teacher = User::factory()->create([
            'status' => UserStatus::Active,
            'vodafone_cash_number' => '01000000000',
            'payment_instructions' => 'حوّل ثم أرسل رقم العملية',
        ]);
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $this->student->assignRole(UserRole::Student);

        $this->parent = User::factory()->create(['status' => UserStatus::Active]);
        $this->parent->assignRole(UserRole::Parent);

        $this->subject = Subject::query()->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);

        app(ParentLinkService::class)->linkDirectly(
            $this->teacher,
            $this->parent,
            $this->student,
            ParentRelationship::Father->value,
        );
    }

    public function test_parent_can_submit_vodafone_proof_for_linked_child(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject, 200);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $payment = app(PaymentRecordService::class)->submitVodafoneProofForChild(
            $this->parent,
            $this->student,
            $subscription,
            ['external_reference' => 'VC-PARENT-1'],
            UploadedFile::fake()->image('parent-proof.jpg'),
        );

        $this->assertSame(PaymentStatus::PendingReview, $payment->status);
        $this->assertSame($this->parent->id, $payment->recorded_by);
        $this->assertSame($this->student->id, $payment->student_id);

        app(PaymentReviewService::class)->confirm($this->teacher, $payment);
        $this->assertTrue($subscription->fresh()->isActive());
    }

    public function test_parent_cannot_pay_for_unlinked_student(): void
    {
        $other = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $other->assignRole(UserRole::Student);
        $this->teacher->students()->attach($other->id, ['joined_at' => now()]);

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($other, $plan);

        $this->expectException(ValidationException::class);
        app(PaymentRecordService::class)->submitVodafoneProofForChild(
            $this->parent,
            $other,
            $subscription,
            ['external_reference' => 'VC-X'],
            UploadedFile::fake()->image('x.jpg'),
        );
    }

    public function test_duplicate_vodafone_proof_is_blocked(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-1'],
            UploadedFile::fake()->image('p1.jpg'),
        );

        $this->expectException(ValidationException::class);
        app(PaymentRecordService::class)->submitVodafoneProof(
            $this->student,
            $subscription,
            ['external_reference' => 'VC-2'],
            UploadedFile::fake()->image('p2.jpg'),
        );
    }

    public function test_cash_rejects_mismatched_student_subscription(): void
    {
        $other = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $other->assignRole(UserRole::Student);
        $this->teacher->students()->attach($other->id, ['joined_at' => now()]);

        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $this->expectException(ValidationException::class);
        app(PaymentRecordService::class)->recordCash($this->teacher, $other, $subscription, []);
    }

    public function test_payment_instructions_prefer_teacher_wallet(): void
    {
        $plan = $this->createPlan($this->teacher, $this->subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($this->student, $plan);

        $info = app(PaymentRecordService::class)->paymentInstructionsForSubscription($subscription);

        $this->assertSame('01000000000', $info['vodafone_cash_number']);
        $this->assertStringContainsString('حوّل', (string) $info['payment_instructions']);
    }

    public function test_teacher_students_and_parent_payment_pages_load(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.students'))
            ->assertOk();

        $this->actingAs($this->teacher)
            ->get(route('teacher.students.show', $this->student))
            ->assertOk()
            ->assertSee($this->student->name, false);

        $this->actingAs($this->parent)
            ->get(route('parent.children.payments', $this->student))
            ->assertOk()
            ->assertSee('فودافون', false);
    }
}
