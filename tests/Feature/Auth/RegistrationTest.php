<?php

namespace Tests\Feature\Auth;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Models\TeacherJoinRequest;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register_as_active_student(): void
    {
        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $subject = Subject::query()->where('grade_id', $grade->id)->firstOrFail();

        $teacher = User::factory()->create([
            'status' => UserStatus::Active,
            'slug' => 'reg-teacher',
            'is_publicly_visible' => true,
            'headline' => 'رياضيات',
            'vodafone_cash_number' => '01099999999',
        ]);
        $teacher->assignRole(UserRole::Teacher);
        $teacher->teachingSubjects()->sync([$subject->id]);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('role', 'student')
            ->set('gradeId', $grade->id)
            ->set('teacherId', $teacher->id)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('teachers.show', $teacher->slug, absolute: false));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue($user->hasRole(UserRole::Student));
        $this->assertNotEmpty($user->student_code);
        $this->assertTrue($user->grades()->where('grades.id', $grade->id)->exists());

        $this->assertDatabaseHas('teacher_join_requests', [
            'student_id' => $user->id,
            'teacher_id' => $teacher->id,
            'status' => JoinRequestStatus::Pending->value,
        ]);
    }

    public function test_student_registration_requires_teacher(): void
    {
        $grade = Grade::query()->where('code', 'S1')->firstOrFail();

        Volt::test('pages.auth.register')
            ->set('name', 'No Teacher')
            ->set('email', 'noteacher@example.com')
            ->set('role', 'student')
            ->set('gradeId', $grade->id)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['teacherId']);
    }
}
