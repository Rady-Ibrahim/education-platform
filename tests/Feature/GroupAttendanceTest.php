<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\GroupMembershipStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\GroupAttendanceService;
use App\Modules\Academic\Services\TeacherGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GroupAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function seedTeacherWithGroupAndStudent(): array
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'برمجة',
            'code' => 'PROG_ATT',
            'ordering' => 1,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->attach($subject->id);

        $student = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $student->assignRole(UserRole::Student);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        $group = app(TeacherGroupService::class)->create($teacher, [
            'subject_id' => $subject->id,
            'name' => 'سبت واتنين',
        ]);

        app(TeacherGroupService::class)->addStudent($teacher, $group, $student, GroupMembershipStatus::Active);

        return compact('teacher', 'student', 'group', 'subject');
    }

    public function test_teacher_can_save_attendance_roster(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'group' => $group] = $this->seedTeacherWithGroupAndStudent();

        $session = app(GroupAttendanceService::class)->saveRoster(
            $teacher,
            $group,
            now()->toDateString(),
            [
                $student->id => AttendanceStatus::Absent->value,
            ],
            'حصة مراجعة',
        );

        $this->assertSame(1, $session->records()->count());
        $this->assertSame(AttendanceStatus::Absent, $session->records->first()->status);
        $this->assertSame(1, app(GroupAttendanceService::class)->todaysAbsentCount($teacher));
    }

    public function test_other_teacher_cannot_save_attendance(): void
    {
        ['group' => $group, 'student' => $student, 'subject' => $subject] = $this->seedTeacherWithGroupAndStudent();

        $other = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $other->assignRole(UserRole::Teacher);
        $other->teachingSubjects()->attach($subject->id);

        $this->expectException(ValidationException::class);
        app(GroupAttendanceService::class)->saveRoster(
            $other,
            $group,
            now()->toDateString(),
            [$student->id => AttendanceStatus::Present->value],
        );
    }

    public function test_attendance_page_ok(): void
    {
        ['teacher' => $teacher] = $this->seedTeacherWithGroupAndStudent();

        $this->actingAs($teacher)
            ->get(route('teacher.attendance'))
            ->assertOk();
    }

    public function test_roster_defaults_to_present_before_save(): void
    {
        ['teacher' => $teacher, 'student' => $student, 'group' => $group] = $this->seedTeacherWithGroupAndStudent();

        $roster = app(GroupAttendanceService::class)->rosterForDate(
            $teacher,
            $group,
            now()->toDateString(),
        );

        $this->assertNull($roster['session']);
        $this->assertSame(AttendanceStatus::Present->value, $roster['students']->first()['status']);
        $this->assertSame($student->id, $roster['students']->first()['id']);
    }
}
