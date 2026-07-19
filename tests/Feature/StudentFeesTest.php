<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\FeeCategory;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Payments\Models\StudentFee;
use App\Modules\Payments\Services\StudentFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentFeesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: User, student: User, parent: User, subject: Subject}
     */
    private function seedActors(): array
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'برمجة',
            'code' => 'PROG_FEES',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create([
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'vodafone_cash_number' => '01000000000',
            'payment_instructions' => 'حوّل واكتب اسم الطالب',
        ]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->attach($subject->id);

        $student = User::factory()->create([
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'student_code' => 'STU-FEE-1',
        ]);
        $student->assignRole(UserRole::Student);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        $parent = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $parent->assignRole(UserRole::Parent);
        app(ParentLinkService::class)->linkDirectly($teacher, $parent, $student);

        return compact('teacher', 'student', 'parent', 'subject');
    }

    public function test_teacher_creates_book_fee_and_collects_cash(): void
    {
        ['teacher' => $teacher, 'student' => $student] = $this->seedActors();
        $fees = app(StudentFeeService::class);

        $fee = $fees->create($teacher, $student, [
            'title' => 'كتاب برمجة',
            'category' => FeeCategory::Books->value,
            'expected_amount' => 150,
        ]);

        $this->assertSame(ChargeStatus::Due, $fee->status);
        $this->assertSame(150.0, $fee->remainingAmount());

        $payment = $fees->collectCash($teacher, $fee, ['amount' => 150]);

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(PaymentChannel::Cash, $payment->channel);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame(ChargeStatus::Paid, $fee->fresh()->status);
    }

    public function test_parent_pays_fee_via_vodafone_and_teacher_confirms(): void
    {
        Storage::fake('public');
        ['teacher' => $teacher, 'student' => $student, 'parent' => $parent] = $this->seedActors();
        $fees = app(StudentFeeService::class);

        $fee = $fees->create($teacher, $student, [
            'title' => 'ملزمة',
            'category' => FeeCategory::Materials->value,
            'expected_amount' => 80,
        ]);

        $payment = $fees->submitVodafoneForChild($parent, $student, $fee, [
            'external_reference' => 'VF-123456',
        ], UploadedFile::fake()->image('proof.jpg'));

        $this->assertSame(PaymentStatus::PendingReview, $payment->status);
        $this->assertSame($fee->id, $payment->student_fee_id);

        $confirmed = app(\App\Modules\Payments\Services\PaymentReviewService::class)
            ->confirm($teacher, $payment);

        $this->assertSame(PaymentStatus::Confirmed, $confirmed->status);
        $this->assertSame(ChargeStatus::Paid, $fee->fresh()->status);
        $this->assertNotNull($confirmed->invoice);
    }

    public function test_cash_collection_blocks_after_vodafone_paid(): void
    {
        Storage::fake('public');
        ['teacher' => $teacher, 'student' => $student, 'parent' => $parent] = $this->seedActors();
        $fees = app(StudentFeeService::class);

        $fee = $fees->create($teacher, $student, [
            'title' => 'كتب',
            'expected_amount' => 100,
        ]);

        $payment = $fees->submitVodafoneForChild($parent, $student, $fee, [
            'external_reference' => 'VF-999',
        ], UploadedFile::fake()->image('proof.jpg'));

        app(\App\Modules\Payments\Services\PaymentReviewService::class)->confirm($teacher, $payment);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $fees->collectCash($teacher, $fee->fresh());
    }
}
