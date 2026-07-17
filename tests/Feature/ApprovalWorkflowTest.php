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

    public function test_self_registered_student_is_pending_admin(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'طالب تجريبي',
            'email' => 'student@test.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $this->assertTrue($user->hasRole(UserRole::Student));
        $this->assertSame(UserStatus::PendingAdmin, $user->status);
        $this->assertFalse($user->isActive());
    }

    public function test_pending_user_is_redirected_to_waiting_page(): void
    {
        $user = User::factory()->pendingAdmin()->create();
        $user->assignRole(UserRole::Student);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('account.pending'));
    }

    public function test_admin_can_approve_pending_teacher(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $teacher = User::factory()->pendingAdmin()->create();
        $teacher->assignRole(UserRole::Teacher);

        app(UserApprovalService::class)->approve($teacher, $admin);

        $this->assertTrue($teacher->fresh()->isActive());
        $this->assertSame($admin->id, $teacher->fresh()->approved_by);
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
