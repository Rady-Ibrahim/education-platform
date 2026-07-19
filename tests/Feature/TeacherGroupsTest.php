<?php

namespace Tests\Feature;

use App\Enums\GroupMembershipStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Services\TeacherGroupService;
use App\Modules\Identity\Services\TeacherStudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherGroupsTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): array
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'برمجة',
            'code' => 'PROG_G',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->attach($subject->id);

        return compact('grade', 'subject', 'teacher');
    }

    public function test_teacher_can_create_multiple_groups_per_grade(): void
    {
        ['subject' => $subject, 'teacher' => $teacher] = $this->seedBase();
        $service = app(TeacherGroupService::class);

        $a = $service->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'سبت واتنين',
            'schedule_note' => '5م',
        ]);

        $b = $service->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'جمعة',
            'schedule_note' => '4م',
        ]);

        $this->assertSame($subject->grade_id, $a->grade_id);
        $this->assertSame($subject->grade_id, $b->grade_id);
        $this->assertSame(2, TeacherGroup::query()->where('teacher_id', $teacher->id)->count());
    }

    public function test_teacher_can_add_student_and_change_membership_status(): void
    {
        ['subject' => $subject, 'teacher' => $teacher] = $this->seedBase();
        $service = app(TeacherGroupService::class);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        $group = $service->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'سبت واتنين',
        ]);

        $service->addStudent($teacher, $group, $student);
        $this->assertTrue(
            $group->students()->where('users.id', $student->id)
                ->wherePivot('status', GroupMembershipStatus::Active->value)
                ->exists()
        );

        $service->updateMembershipStatus($teacher, $group, $student, GroupMembershipStatus::Frozen);
        $this->assertTrue(
            $group->fresh()->students()->where('users.id', $student->id)
                ->wherePivot('status', GroupMembershipStatus::Frozen->value)
                ->exists()
        );
    }

    public function test_other_teacher_cannot_manage_group(): void
    {
        ['subject' => $subject, 'teacher' => $teacher] = $this->seedBase();
        $service = app(TeacherGroupService::class);

        $group = $service->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'مجموعة أ',
        ]);

        $other = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $other->assignRole(UserRole::Teacher);
        $other->teachingSubjects()->attach($subject->id);

        $this->expectException(ValidationException::class);
        $service->update($other, $group, ['name' => 'اختراق']);
    }

    public function test_create_student_can_assign_to_group(): void
    {
        ['grade' => $grade, 'subject' => $subject, 'teacher' => $teacher] = $this->seedBase();

        $group = app(TeacherGroupService::class)->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'سبت واتنين',
        ]);

        $result = app(TeacherStudentService::class)->createStudent($teacher, [
            'name' => 'طالب مجموعة',
            'email' => 'group-student@education.test',
            'grade_id' => $grade->id,
            'group_id' => $group->id,
        ]);

        $this->assertTrue(
            $group->students()->where('users.id', $result['user']->id)->exists()
        );
    }

    public function test_teacher_groups_page_ok(): void
    {
        ['teacher' => $teacher] = $this->seedBase();

        $this->actingAs($teacher)
            ->get(route('teacher.groups'))
            ->assertOk();
    }
}
