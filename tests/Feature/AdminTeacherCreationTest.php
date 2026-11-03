<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\AdminTeacherService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminTeacherCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->assignRole(UserRole::Admin);
    }

    public function test_admin_users_page_shows_create_teacher_tab(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('إنشاء مدرس', false);
    }

    public function test_admin_creates_teacher_with_catalog_subject(): void
    {
        $service = app(AdminTeacherService::class);
        $subject = Subject::query()->where('is_custom', false)->firstOrFail();

        $result = $service->createTeacher($this->admin, [
            'name'         => 'مدرس جديد',
            'email'        => 'new.teacher@test.com',
            'phone'        => '01011111111',
            'headline'     => 'مدرس رياضيات',
            'subject_mode' => 'catalog',
            'subject_id'   => $subject->id,
        ]);

        $teacher = $result['user'];

        // Account is Active immediately
        $this->assertSame(UserStatus::Active, $teacher->status);
        $this->assertTrue($teacher->hasRole(UserRole::Teacher));
        $this->assertNotEmpty($result['plain_password']);
        $this->assertSame($this->admin->id, $teacher->created_by);
        $this->assertNotEmpty($teacher->slug);

        // Subject assigned
        $this->assertTrue($teacher->teachingSubjects()->where('subjects.id', $subject->id)->exists());

        // Platform billing started
        $this->assertDatabaseHas('platform_subscriptions', ['teacher_id' => $teacher->id]);
    }

    public function test_admin_creates_teacher_with_custom_subject(): void
    {
        $service = app(AdminTeacherService::class);
        $grade   = Grade::query()->where('code', 'S1')->firstOrFail();

        $result = $service->createTeacher($this->admin, [
            'name'         => 'مدرسة فيزياء',
            'email'        => 'physics.teacher@test.com',
            'subject_mode' => 'custom',
            'grade_id'     => $grade->id,
            'subject_name' => 'فيزياء',
        ]);

        $teacher = $result['user'];
        $this->assertTrue($teacher->isActive());
        $this->assertSame(1, $teacher->teachingSubjects()->count());
        $this->assertSame('فيزياء', $teacher->teachingSubjects()->first()->name);
    }

    public function test_admin_creates_teacher_without_subject(): void
    {
        $service = app(AdminTeacherService::class);

        $result = $service->createTeacher($this->admin, [
            'name'         => 'مدرس بدون مادة',
            'email'        => 'no.subject@test.com',
            'subject_mode' => null,
        ]);

        $teacher = $result['user'];
        $this->assertTrue($teacher->isActive());
        $this->assertSame(0, $teacher->teachingSubjects()->count());
    }

    public function test_non_admin_cannot_create_teacher(): void
    {
        $nonAdmin = User::factory()->create(['status' => UserStatus::Active]);
        $nonAdmin->assignRole(UserRole::Teacher);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(AdminTeacherService::class)->createTeacher($nonAdmin, [
            'name'  => 'اختراق',
            'email' => 'hack@test.com',
        ]);
    }

    public function test_duplicate_email_fails_validation(): void
    {
        $service = app(AdminTeacherService::class);

        $service->createTeacher($this->admin, [
            'name'  => 'مدرس أول',
            'email' => 'duplicate@test.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $service->createTeacher($this->admin, [
            'name'  => 'مدرس ثاني',
            'email' => 'duplicate@test.com', // same email
        ]);
    }

    public function test_created_teacher_can_login_immediately(): void
    {
        $service = app(AdminTeacherService::class);

        $result  = $service->createTeacher($this->admin, [
            'name'  => 'مدرس للدخول',
            'email' => 'login.teacher@test.com',
        ]);

        $teacher = $result['user']->fresh();

        // Teacher should be able to access their dashboard (no pending state)
        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk();
    }

    public function test_admin_users_page_create_tab_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertOk();
    }
}
