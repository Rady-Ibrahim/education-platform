<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PlatformSubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\PlatformPaymentService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::Admin);

        $this->teacher = User::factory()->create(['approved_at' => now()]);
        $this->teacher->assignRole(UserRole::Teacher);
    }

    public function test_teacher_gets_three_month_trial_by_default(): void
    {
        $subscription = app(PlatformBillingService::class)->ensureSubscription($this->teacher);

        $this->assertSame(PlatformSubscriptionStatus::Trialing, $subscription->status);
        $this->assertTrue($subscription->trial_ends_at->greaterThan(now()->addDays(80)));
        $this->assertTrue(app(PlatformBillingService::class)->teacherHasAccess($this->teacher));
    }

    public function test_expired_trial_blocks_teacher_panel_and_allows_platform_page(): void
    {
        $billing = app(PlatformBillingService::class);
        $subscription = $billing->ensureSubscription($this->teacher);
        $subscription->update([
            'status' => PlatformSubscriptionStatus::PastDue,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.platform'));

        $this->actingAs($this->teacher)
            ->get(route('teacher.platform'))
            ->assertOk();
    }

    public function test_admin_confirms_platform_vodafone_payment(): void
    {
        $billing = app(PlatformBillingService::class);
        $billing->updateSettings($this->admin, [
            'vodafone_cash_number' => '01099999999',
            'monthly_fee' => 150,
            'trial_days' => 90,
            'period_days' => 30,
        ]);

        $subscription = $billing->ensureSubscription($this->teacher);
        $subscription->update([
            'status' => PlatformSubscriptionStatus::PastDue,
            'trial_ends_at' => now()->subDay(),
        ]);

        $payment = app(PlatformPaymentService::class)->submitVodafoneProof($this->teacher, [
            'external_reference' => 'PLAT-1234',
        ]);

        $this->assertSame(PaymentStatus::PendingReview, $payment->status);

        app(PlatformPaymentService::class)->confirm($this->admin, $payment);

        $this->assertSame(PlatformSubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertTrue($billing->teacherHasAccess($this->teacher->fresh()));
    }

    public function test_student_vodafone_blocked_by_default(): void
    {
        config(['payments.student_vodafone_enabled' => false]);

        $this->expectException(ValidationException::class);

        // Will fail early before needing full subscription setup if we call with minimal mocks —
        // use real enrollment path via a lightweight assert on service message by calling with factory stubs.
        $student = User::factory()->create();
        $student->assignRole(UserRole::Student);

        // Create a fake subscription row isn't needed if assert happens before DB ops —
        // PaymentRecordService checks config first then assertStudentOwnsSubscription.
        $subscription = new \App\Modules\Payments\Models\Subscription([
            'student_id' => $student->id,
            'status' => \App\Enums\SubscriptionStatus::PendingPayment,
        ]);
        $subscription->id = 1;

        app(\App\Modules\Payments\Services\PaymentRecordService::class)
            ->submitVodafoneProof($student, $subscription, ['external_reference' => 'X']);
    }
}
