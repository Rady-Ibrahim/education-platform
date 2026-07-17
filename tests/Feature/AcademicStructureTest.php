<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    private AcademicStructureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $this->service = app(AcademicStructureService::class);
    }

    public function test_academic_seed_creates_hierarchy(): void
    {
        $this->assertTrue(Stage::query()->where('code', 'PRIMARY')->exists());
        $this->assertTrue(Grade::query()->where('code', 'G1')->exists());
        $this->assertTrue(Subject::query()->where('code', 'MATH')->exists());
    }

    public function test_admin_can_open_academic_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('admin.academic'))
            ->assertOk();
    }

    public function test_teacher_only_sees_assigned_subjects(): void
    {
        $teacherA = User::factory()->create();
        $teacherA->assignRole(UserRole::Teacher);

        $teacherB = User::factory()->create();
        $teacherB->assignRole(UserRole::Teacher);

        $subject = Subject::query()->firstOrFail();

        $this->service->assignTeacherToSubject($teacherA, $subject);

        $forA = $this->service->subjectsForTeacher($teacherA);
        $forB = $this->service->subjectsForTeacher($teacherB);

        $this->assertTrue($forA->contains('id', $subject->id));
        $this->assertFalse($forB->contains('id', $subject->id));
    }

    public function test_student_can_be_enrolled_in_grade(): void
    {
        $student = User::factory()->create();
        $student->assignRole(UserRole::Student);

        $grade = Grade::query()->firstOrFail();

        $this->service->enrollStudentInGrade($student, $grade);

        $this->assertTrue($student->grades()->where('grades.id', $grade->id)->exists());
    }

    public function test_teacher_cannot_access_admin_academic_page(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $this->actingAs($teacher)
            ->get(route('admin.academic'))
            ->assertForbidden();
    }

    public function test_create_stage_grade_subject_unit_chain(): void
    {
        $stage = $this->service->createStage(['name' => 'رياض أطفال']);
        $grade = $this->service->createGrade($stage, ['name' => 'KG1']);
        $subject = $this->service->createSubject($grade, ['name' => 'مهارات']);
        $unit = $this->service->createUnit($subject, ['name' => 'الأسبوع الأول']);

        $this->assertDatabaseHas('stages', ['id' => $stage->id, 'name' => 'رياض أطفال']);
        $this->assertDatabaseHas('grades', ['id' => $grade->id, 'stage_id' => $stage->id]);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'grade_id' => $grade->id]);
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'subject_id' => $subject->id]);
    }
}
