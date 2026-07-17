<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\TeacherProfileService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherCatalogTest extends TestCase
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

    public function test_public_catalog_lists_only_visible_teachers(): void
    {
        $hidden = User::factory()->create([
            'status' => UserStatus::Active,
            'slug' => 'hidden-teacher',
            'is_publicly_visible' => false,
            'headline' => 'مخفي',
            'vodafone_cash_number' => '01011111111',
        ]);
        $hidden->assignRole(UserRole::Teacher);

        $visible = User::factory()->create([
            'name' => 'مدرس ظاهر',
            'status' => UserStatus::Active,
            'slug' => 'visible-teacher',
            'is_publicly_visible' => true,
            'headline' => 'رياضيات',
            'bio' => 'نبذة',
            'vodafone_cash_number' => '01022222222',
        ]);
        $visible->assignRole(UserRole::Teacher);

        $this->get(route('teachers.index'))
            ->assertOk()
            ->assertSee('مدرس ظاهر', false)
            ->assertDontSee('مخفي', false);
    }

    public function test_teacher_show_page_and_join_from_register_flow(): void
    {
        $stage = Stage::query()->create(['name' => 'ثانوي', 'code' => 'SEC', 'ordering' => 1, 'is_active' => true]);
        $grade = Grade::query()->create(['stage_id' => $stage->id, 'name' => 'أولى', 'code' => 'SEC1', 'ordering' => 1, 'is_active' => true]);
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'رياضيات',
            'code' => 'MATH1',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = app(RegistrationService::class)->register([
            'name' => 'أحمد علي',
            'email' => 'ahmed@test.com',
            'password' => 'password',
            'role' => 'teacher',
            'headline' => 'مدرس رياضيات',
            'bio' => 'خبرة 10 سنين',
            'vodafone_cash_number' => '01033333333',
            'is_publicly_visible' => true,
            'subject_mode' => 'catalog',
            'subject_id' => $subject->id,
        ]);

        $this->assertTrue($teacher->is_publicly_visible);

        $this->get(route('teachers.show', $teacher->slug))
            ->assertOk()
            ->assertSee('أحمد علي', false)
            ->assertSee('01033333333', false);
    }

    public function test_admin_can_hide_teacher_from_catalog(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $teacher = User::factory()->create([
            'status' => UserStatus::Active,
            'slug' => 'to-hide',
            'is_publicly_visible' => true,
            'headline' => 'فيزياء',
            'vodafone_cash_number' => '01044444444',
        ]);
        $teacher->assignRole(UserRole::Teacher);

        app(\App\Modules\Identity\Services\UserApprovalService::class)
            ->hideFromCatalog($teacher, $admin);

        $this->assertFalse($teacher->fresh()->is_publicly_visible);

        $this->get(route('teachers.index'))
            ->assertOk()
            ->assertDontSee('فيزياء', false);
    }

    public function test_teacher_profile_requires_fields_for_public_visibility(): void
    {
        $teacher = User::factory()->create(['status' => UserStatus::Active]);
        $teacher->assignRole(UserRole::Teacher);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(TeacherProfileService::class)->update($teacher, [
            'is_publicly_visible' => true,
            'subject_mode' => 'catalog',
            'subject_id' => null,
        ]);
    }

    public function test_catalog_can_filter_teachers_by_grade(): void
    {
        $stage = Stage::query()->create(['name' => 'ثانوي', 'code' => 'SECX', 'ordering' => 1, 'is_active' => true]);
        $gradeA = Grade::query()->create(['stage_id' => $stage->id, 'name' => 'أولى', 'code' => 'GA', 'ordering' => 1, 'is_active' => true]);
        $gradeB = Grade::query()->create(['stage_id' => $stage->id, 'name' => 'تانية', 'code' => 'GB', 'ordering' => 2, 'is_active' => true]);

        $subjectA = Subject::query()->create([
            'grade_id' => $gradeA->id,
            'name' => 'عربي أولى',
            'code' => 'ARA',
            'ordering' => 1,
            'is_active' => true,
        ]);
        $subjectB = Subject::query()->create([
            'grade_id' => $gradeB->id,
            'name' => 'عربي تانية',
            'code' => 'ARB',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacherA = User::factory()->create([
            'name' => 'مدرس أولى',
            'status' => UserStatus::Active,
            'slug' => 'teacher-grade-a',
            'is_publicly_visible' => true,
            'vodafone_cash_number' => '01055555551',
        ]);
        $teacherA->assignRole(UserRole::Teacher);
        $teacherA->teachingSubjects()->sync([$subjectA->id]);

        $teacherB = User::factory()->create([
            'name' => 'مدرس تانية',
            'status' => UserStatus::Active,
            'slug' => 'teacher-grade-b',
            'is_publicly_visible' => true,
            'vodafone_cash_number' => '01055555552',
        ]);
        $teacherB->assignRole(UserRole::Teacher);
        $teacherB->teachingSubjects()->sync([$subjectB->id]);

        $catalog = app(\App\Modules\Identity\Services\TeacherCatalogService::class);

        $filtered = $catalog->paginate(null, null, $gradeA->id);
        $names = $filtered->pluck('name')->all();

        $this->assertContains('مدرس أولى', $names);
        $this->assertNotContains('مدرس تانية', $names);
    }
}
