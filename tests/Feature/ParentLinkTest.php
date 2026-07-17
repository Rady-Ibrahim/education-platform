<?php

namespace Tests\Feature;

use App\Enums\ParentLinkStatus;
use App\Enums\ParentRelationship;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\StudentCodeService;
use App\Modules\Identity\Services\UserApprovalService;
use App\Modules\Reports\Services\DashboardReportService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParentLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $student;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->parent = User::factory()->create(['status' => UserStatus::Active]);
        $this->parent->assignRole(UserRole::Parent);

        $this->student = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $this->student->assignRole(UserRole::Student);

        $this->teacher = User::factory()->create(['status' => UserStatus::Active]);
        $this->teacher->assignRole(UserRole::Teacher);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_parent_can_register_as_pending(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'ولي أمر',
            'email' => 'parent@test.com',
            'password' => 'password',
            'role' => 'parent',
        ]);

        $this->assertTrue($user->hasRole(UserRole::Parent));
        $this->assertSame(UserStatus::PendingAdmin, $user->status);
    }

    public function test_student_self_registration_gets_student_code(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'طالب',
            'email' => 'stu@test.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $this->assertNotEmpty($user->student_code);
        $this->assertStringStartsWith('STU-', $user->student_code);
    }

    public function test_parent_request_requires_student_approval(): void
    {
        $link = app(ParentLinkService::class)->requestByStudentCode(
            $this->parent,
            $this->student->student_code,
            ParentRelationship::Father->value,
        );

        $this->assertSame(ParentLinkStatus::Pending, $link->status);
        $this->assertFalse(app(ParentLinkService::class)->parentCanViewStudent($this->parent, $this->student));

        app(ParentLinkService::class)->approveByStudent($link, $this->student);

        $this->assertTrue($link->fresh()->isActive());
        $this->assertTrue(app(ParentLinkService::class)->parentCanViewStudent($this->parent, $this->student));
    }

    public function test_other_student_cannot_approve_link(): void
    {
        $other = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $other->assignRole(UserRole::Student);

        $link = app(ParentLinkService::class)->requestByStudentCode(
            $this->parent,
            $this->student->student_code,
        );

        $this->expectException(ValidationException::class);
        app(ParentLinkService::class)->approveByStudent($link, $other);
    }

    public function test_admin_can_link_directly(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(UserRole::Admin);

        $link = app(ParentLinkService::class)->linkDirectly(
            $admin,
            $this->parent,
            $this->student,
            ParentRelationship::Mother->value,
        );

        $this->assertSame(ParentLinkStatus::Active, $link->status);
        $this->assertTrue(app(ParentLinkService::class)->parentCanViewStudent($this->parent, $this->student));
    }

    public function test_teacher_can_link_only_own_student(): void
    {
        $outsider = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $outsider->assignRole(UserRole::Student);

        app(ParentLinkService::class)->linkDirectly($this->teacher, $this->parent, $this->student);
        $this->assertTrue(app(ParentLinkService::class)->parentCanViewStudent($this->parent, $this->student));

        $this->expectException(ValidationException::class);
        app(ParentLinkService::class)->linkDirectly($this->teacher, $this->parent, $outsider);
    }

    public function test_parent_dashboard_shows_child_kpis(): void
    {
        app(ParentLinkService::class)->linkDirectly($this->teacher, $this->parent, $this->student);

        $stats = app(DashboardReportService::class)->forParent($this->parent);

        $this->assertSame(1, $stats['children_count']);
        $this->assertSame($this->student->id, $stats['children'][0]['id']);
    }

    public function test_parent_dashboard_page_loads_after_approval(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(UserRole::Admin);

        $pendingParent = app(RegistrationService::class)->register([
            'name' => 'ولي',
            'email' => 'parent2@test.com',
            'password' => 'password',
            'role' => 'parent',
        ]);
        app(UserApprovalService::class)->approve($pendingParent, $admin);

        $this->actingAs($pendingParent->fresh())
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('ربط ابن بكود الطالب', false);
    }
}
