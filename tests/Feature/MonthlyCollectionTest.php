<?php

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Payments\Models\SubscriptionCharge;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\MonthlyCollectionService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function seedTeacherStudentPlan(): array
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'برمجة',
            'code' => 'PROG_LEDGER',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->attach($subject->id);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        $plan = app(SubscriptionPlanService::class)->create($teacher, $subject, [
            'name' => 'شهري',
            'price' => 400,
            'duration_days' => 30,
        ]);

        $subscription = app(EnrollmentService::class)->enrollStudentByTeacher($teacher, $student, $plan);

        return compact('teacher', 'student', 'subject', 'plan', 'subscription');
    }

    public function test_enroll_creates_current_month_charge(): void
    {
        ['subscription' => $subscription] = $this->seedTeacherStudentPlan();

        $charge = SubscriptionCharge::query()
            ->where('subscription_id', $subscription->id)
            ->first();

        $this->assertNotNull($charge);
        $this->assertSame(ChargeStatus::Due, $charge->status);
        $this->assertEquals(400.0, (float) $charge->expected_amount);
        $this->assertTrue($charge->billing_month->isSameMonth(now()));
    }

    public function test_partial_then_full_collection_with_receipt(): void
    {
        ['teacher' => $teacher, 'subscription' => $subscription] = $this->seedTeacherStudentPlan();
        $collection = app(MonthlyCollectionService::class);
        $charge = $collection->ensureChargeForSubscription($subscription);

        $first = $collection->collectCash($teacher, $charge, ['amount' => 150]);
        $this->assertNotNull($first->receipt_number);
        $this->assertSame(ChargeStatus::Partial, $charge->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertEquals(250.0, $charge->fresh()->remainingAmount());

        $second = $collection->collectCash($teacher, $charge->fresh(), ['amount' => 250]);
        $this->assertSame(ChargeStatus::Paid, $charge->fresh()->status);
        $this->assertNotSame($first->receipt_number, $second->receipt_number);
    }

    public function test_discount_reduces_remaining(): void
    {
        ['teacher' => $teacher, 'subscription' => $subscription] = $this->seedTeacherStudentPlan();
        $collection = app(MonthlyCollectionService::class);
        $charge = $collection->ensureChargeForSubscription($subscription);

        $collection->collectCash($teacher, $charge, [
            'amount' => 300,
            'discount' => 100,
        ]);

        $this->assertSame(ChargeStatus::Paid, $charge->fresh()->status);
        $this->assertEquals(0.0, $charge->fresh()->remainingAmount());
    }

    public function test_owing_list_for_month(): void
    {
        ['teacher' => $teacher] = $this->seedTeacherStudentPlan();
        $collection = app(MonthlyCollectionService::class);

        $this->assertSame(1, $collection->owingCountForMonth($teacher));
        $this->assertEquals(400.0, $collection->owingTotalForMonth($teacher));
    }

    public function test_payments_page_ok(): void
    {
        ['teacher' => $teacher] = $this->seedTeacherStudentPlan();

        $this->actingAs($teacher)
            ->get(route('teacher.payments', ['tab' => 'cash']))
            ->assertOk();
    }
}
