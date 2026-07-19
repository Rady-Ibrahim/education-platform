<?php

namespace Tests\Feature;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamDeliveryMode;
use App\Enums\ParentLinkStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Identity\Models\ParentStudentLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualExamAndParentResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_paper_exam_and_record_manual_grade(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'برمجة',
            'code' => 'PROG_T',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->attach($subject->id);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        $exam = app(ExamService::class)->create($teacher, $subject, [
            'title' => 'امتحان ورقي',
            'delivery_mode' => ExamDeliveryMode::Paper->value,
            'manual_max_score' => 100,
            'pass_score' => 50,
            'is_published' => true,
        ]);

        $this->assertTrue($exam->isPaper());
        $this->assertSame(100.0, (float) $exam->manual_max_score);

        $attempt = app(ExamAttemptService::class)->recordManualScore($teacher, $exam, $student, 85);

        $this->assertSame(ExamAttemptStatus::Graded, $attempt->status);
        $this->assertSame(85.0, (float) $attempt->score);
        $this->assertSame(100.0, (float) $attempt->max_score);
    }

    public function test_parent_can_open_child_exam_results_page(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $parent = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $parent->assignRole(UserRole::Parent);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);

        ParentStudentLink::query()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => ParentLinkStatus::Active,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.exams', $student))
            ->assertOk();
    }

    public function test_parent_cannot_open_unlinked_child_exams(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $parent = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $parent->assignRole(UserRole::Parent);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);

        $this->actingAs($parent)
            ->get(route('parent.children.exams', $student))
            ->assertForbidden();
    }
}
