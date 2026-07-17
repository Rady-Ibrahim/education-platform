<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Services\BunnyStreamService;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\TeacherProfileService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherSubjectsAndVideoTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Grade $grade;

    private Subject $catalogSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $stage = Stage::query()->create([
            'name' => 'ثانوي',
            'code' => 'SEC',
            'ordering' => 1,
            'is_active' => true,
        ]);
        $this->grade = Grade::query()->create([
            'stage_id' => $stage->id,
            'name' => 'أولى',
            'code' => 'SEC1',
            'ordering' => 1,
            'is_active' => true,
        ]);
        $this->catalogSubject = Subject::query()->create([
            'grade_id' => $this->grade->id,
            'name' => 'رياضيات',
            'code' => 'MATH',
            'ordering' => 1,
            'is_active' => true,
            'is_custom' => false,
        ]);
    }

    public function test_teacher_sets_one_catalog_subject_from_profile(): void
    {
        $subject = app(TeacherProfileService::class)->setSingleSubject($this->teacher, [
            'subject_mode' => 'catalog',
            'subject_id' => $this->catalogSubject->id,
        ]);

        $this->assertSame($this->catalogSubject->id, $subject->id);
        $this->assertSame(1, $this->teacher->teachingSubjects()->count());
    }

    public function test_teacher_writes_custom_subject_name_from_profile(): void
    {
        $subject = app(TeacherProfileService::class)->setSingleSubject($this->teacher, [
            'subject_mode' => 'custom',
            'grade_id' => $this->grade->id,
            'subject_name' => 'مراجعة خاصة',
        ]);

        $this->assertTrue($subject->is_custom);
        $this->assertSame('مراجعة خاصة', $subject->name);
        $this->assertSame(1, $this->teacher->teachingSubjects()->count());
    }

    public function test_teacher_register_with_single_custom_subject(): void
    {
        $user = app(RegistrationService::class)->register([
            'name' => 'مدرس جديد',
            'email' => 'new-teacher@test.com',
            'password' => 'password',
            'role' => 'teacher',
            'headline' => 'فيزياء',
            'vodafone_cash_number' => '01055555555',
            'subject_mode' => 'custom',
            'grade_id' => $this->grade->id,
            'subject_name' => 'فيزياء',
            'is_publicly_visible' => true,
        ]);

        $this->assertSame(1, $user->teachingSubjects()->count());
        $this->assertSame('فيزياء', $user->teachingSubjects()->first()->name);
        $this->assertTrue($user->is_publicly_visible);
    }

    public function test_bunny_create_and_upload_uses_stream_api(): void
    {
        config([
            'bunny.library_id' => 'lib123',
            'bunny.token_auth_key' => 'token-key',
            'bunny.stream_api_key' => 'api-key',
            'bunny.api_base_url' => 'https://video.bunnycdn.com',
        ]);

        Http::fake([
            'video.bunnycdn.com/library/lib123/videos' => Http::response(['guid' => 'vid-guid-1'], 200),
            'video.bunnycdn.com/library/lib123/videos/vid-guid-1' => Http::response([], 200),
        ]);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('lesson.webm', 100, 'video/webm');

        $id = app(BunnyStreamService::class)->createAndUpload('حصة تجريبية', $file);

        $this->assertSame('vid-guid-1', $id);
        Http::assertSentCount(2);
    }

    public function test_teacher_lessons_page_points_to_profile_when_no_subject(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.lessons'))
            ->assertOk()
            ->assertSee('حدّد مادتك أولًا من', false)
            ->assertDontSee('مادة مش موجودة في الكتالوج', false);
    }
}
