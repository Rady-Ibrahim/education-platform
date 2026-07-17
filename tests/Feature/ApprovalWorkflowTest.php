<?php

namespace Tests\Feature;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\TeacherJoinService;
use App\Modules\Identity\Services\TeacherStudentService;
use App\Modules\Identity\Services\UserApprovalService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    public function test_self_registered_student_is_active_immediately(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'طالب تجريبي',
            'email' => 'student@test.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $this->assertTrue($user->hasRole(UserRole::Student));
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue($user->isActive());
    }

    public function test_self_registered_teacher_is_active_immediately(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'مدرس تجريبي',
            'email' => 'teacher@test.com',
            'password' => 'password',
            'role' => 'teacher',
            'headline' => 'رياضيات',
            'vodafone_cash_number' => '01000000000',
        ]);

        $this->assertTrue($user->hasRole(UserRole::Teacher));
        $this->assertTrue($user->isActive());
        $this->assertNotEmpty($user->slug);
        $this->assertFalse($user->is_publicly_visible);
    }

    public function test_suspended_user_is_blocked_from_panels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $student = User::factory()->create();
        $student->assignRole(UserRole::Student);

        app(UserApprovalService::class)->suspend($student, $admin, 'مخالفة');

        $this->actingAs($student->fresh())
            ->get(route('student.dashboard'))
            ->assertRedirect();
    }

    public function test_admin_can_unsuspend_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $teacher = User::factory()->create(['status' => UserStatus::Suspended]);
        $teacher->assignRole(UserRole::Teacher);

        app(UserApprovalService::class)->unsuspend($teacher, $admin);

        $this->assertTrue($teacher->fresh()->isActive());
    }

    public function test_teacher_created_student_is_active_and_linked(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $result = app(TeacherStudentService::class)->createStudent($teacher, [
            'name' => 'طالب يدوي',
            'email' => 'manual@test.com',
            'phone' => '01011112222',
        ]);

        $student = $result['user'];

        $this->assertTrue($student->isActive());
        $this->assertTrue($student->hasRole(UserRole::Student));
        $this->assertSame($teacher->id, $student->created_by);
        $this->assertTrue($teacher->students()->where('users.id', $student->id)->exists());
        $this->assertNotEmpty($result['plain_password']);
    }

    public function test_student_join_request_requires_teacher_approval(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $student = User::factory()->create();
        $student->assignRole(UserRole::Student);

        $join = app(TeacherJoinService::class)->requestJoin($student, $teacher, 'عايز انضم');

        $this->assertSame(JoinRequestStatus::Pending, $join->status);
        $this->assertFalse($teacher->students()->where('users.id', $student->id)->exists());

        app(TeacherJoinService::class)->approve($join, $teacher);

        $this->assertSame(JoinRequestStatus::Approved, $join->fresh()->status);
        $this->assertTrue($teacher->students()->where('users.id', $student->id)->exists());
    }

    public function test_other_teacher_cannot_approve_join_request(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole(UserRole::Teacher);

        $student = User::factory()->create();
        $student->assignRole(UserRole::Student);

        $join = app(TeacherJoinService::class)->requestJoin($student, $teacher);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TeacherJoinService::class)->approve($join, $otherTeacher);
    }
}
