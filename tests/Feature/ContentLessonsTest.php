<?php

namespace Tests\Feature;

use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Services\BunnyStreamService;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Content\Services\LessonPlaybackService;
use App\Modules\Content\Services\LessonProgressService;
use App\Modules\Content\Services\LessonService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentLessonsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicStructureService $academic;

    private LessonService $lessons;

    private ContentAccessService $access;

    private LessonProgressService $progress;

    private User $teacher;

    private User $otherTeacher;

    private User $student;

    private User $outsiderStudent;

    private Subject $subject;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $this->academic = app(AcademicStructureService::class);
        $this->lessons = app(LessonService::class);
        $this->access = app(ContentAccessService::class);
        $this->progress = app(LessonProgressService::class);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->outsiderStudent = User::factory()->create();
        $this->outsiderStudent->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        $this->unit = $this->subject->units()->firstOrFail();

        $this->academic->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_teacher_can_create_text_lesson_on_assigned_subject(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'مقدمة الجبر',
            'type' => LessonType::Text->value,
            'body' => 'محتوى الدرس',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'مقدمة الجبر',
            'created_by' => $this->teacher->id,
            'is_published' => true,
        ]);
    }

    public function test_teacher_cannot_create_lesson_on_unassigned_subject(): void
    {
        $this->expectException(ValidationException::class);

        $this->lessons->create($this->otherTeacher, $this->unit, [
            'title' => 'درس ممنوع',
            'type' => LessonType::Text->value,
        ]);
    }

    public function test_video_lesson_requires_bunny_video_id(): void
    {
        $this->expectException(ValidationException::class);

        $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'فيديو ناقص',
            'type' => LessonType::Video->value,
        ]);
    }

    public function test_teacher_can_create_video_lesson_with_bunny_id(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'شرح معادلات',
            'type' => LessonType::Video->value,
            'bunny_video_id' => 'abc-123',
            'is_published' => true,
        ]);

        $this->assertSame('abc-123', $lesson->bunny_video_id);
        $this->assertTrue($lesson->hasVideo());
    }

    public function test_teacher_can_publish_and_delete_own_lesson(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'مسودة',
            'type' => LessonType::Text->value,
            'is_published' => false,
        ]);

        $this->lessons->publish($this->teacher, $lesson, true);
        $this->assertTrue($lesson->fresh()->is_published);

        $this->lessons->delete($this->teacher, $lesson);
        $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
    }

    public function test_linked_student_can_access_published_lesson(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'درس منشور',
            'type' => LessonType::Text->value,
            'is_published' => true,
        ]);

        $this->assertTrue($this->access->studentCanAccessLesson($this->student, $lesson));
    }

    public function test_student_cannot_access_unpublished_lesson(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'مسودة',
            'type' => LessonType::Text->value,
            'is_published' => false,
        ]);

        $this->assertFalse($this->access->studentCanAccessLesson($this->student, $lesson));
    }

    public function test_unlinked_student_cannot_access_lesson(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'درس محمي',
            'type' => LessonType::Text->value,
            'is_published' => true,
        ]);

        $this->assertFalse($this->access->studentCanAccessLesson($this->outsiderStudent, $lesson));

        $this->expectException(ValidationException::class);
        $this->progress->updateProgress($this->outsiderStudent, $lesson, 10);
    }

    public function test_student_progress_updates_and_completes(): void
    {
        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'تتبع تقدم',
            'type' => LessonType::Text->value,
            'is_published' => true,
        ]);

        $halfway = $this->progress->updateProgress($this->student, $lesson, 40, 120);
        $this->assertSame(40, $halfway->percent);
        $this->assertFalse($halfway->is_completed);

        $done = $this->progress->updateProgress($this->student, $lesson, 100);
        $this->assertSame(100, $done->percent);
        $this->assertTrue($done->is_completed);
        $this->assertNotNull($done->completed_at);
    }

    public function test_bunny_signed_embed_url_is_generated_for_authorized_student(): void
    {
        config([
            'bunny.library_id' => 'lib-1',
            'bunny.token_auth_key' => 'secret-key',
            'bunny.token_ttl_seconds' => 3600,
            'bunny.embed_base_url' => 'https://iframe.mediadelivery.net/embed',
        ]);

        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'فيديو محمي',
            'type' => LessonType::Video->value,
            'bunny_video_id' => 'vid-99',
            'is_published' => true,
        ]);

        $url = app(LessonPlaybackService::class)->signedEmbedUrl($this->student, $lesson);

        $this->assertStringContainsString('lib-1/vid-99', $url);
        $this->assertStringContainsString('token=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    public function test_bunny_token_matches_expected_hash(): void
    {
        config(['bunny.token_auth_key' => 'test-secret']);

        $expires = 1700000000;
        $token = app(BunnyStreamService::class)->makeToken('video-1', $expires);

        $this->assertSame(hash('sha256', 'test-secret'.'video-1'.$expires), $token);
    }

    public function test_teacher_can_add_downloadable_attachment(): void
    {
        Storage::fake('public');

        $lesson = $this->lessons->create($this->teacher, $this->unit, [
            'title' => 'مع مرفق',
            'type' => LessonType::Text->value,
            'is_published' => true,
        ]);

        $file = UploadedFile::fake()->create('summary.pdf', 100, 'application/pdf');
        $attachment = $this->lessons->addAttachment($this->teacher, $lesson, $file, true);

        $this->assertTrue($attachment->is_downloadable);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_teacher_and_student_can_open_lesson_pages(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.lessons'))
            ->assertOk();

        $this->actingAs($this->student)
            ->get(route('student.lessons'))
            ->assertOk();
    }

    public function test_student_cannot_open_teacher_lessons_page(): void
    {
        $this->actingAs($this->student)
            ->get(route('teacher.lessons'))
            ->assertForbidden();
    }
}
