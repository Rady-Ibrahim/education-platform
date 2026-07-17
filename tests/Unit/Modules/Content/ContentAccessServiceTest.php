<?php

namespace Tests\Unit\Modules\Content;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentAccessService $access;

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

        $this->access = app(ContentAccessService::class);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
    }

    public function test_linked_without_subscription_cannot_access_content(): void
    {
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);

        $this->assertTrue($this->access->studentIsLinkedToSubjectTeacher($this->student, $this->subject));
        $this->assertFalse($this->access->studentCanAccessSubject($this->student, $this->subject));
    }

    public function test_expired_subscription_denies_access(): void
    {
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);

        $plan = SubscriptionPlan::query()->create([
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'منتهي',
            'price' => 50,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->access->studentCanAccessSubject($this->student, $this->subject));
    }

    public function test_active_subscription_grants_access(): void
    {
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);

        $plan = SubscriptionPlan::query()->create([
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'نشط',
            'price' => 50,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertTrue($this->access->studentCanAccessSubject($this->student, $this->subject));
    }
}
