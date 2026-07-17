<?php

namespace Tests\Feature;

use App\Enums\LessonType;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Certificates\Models\Certificate;
use App\Modules\Certificates\Services\CertificateService;
use App\Modules\Content\Services\AttachmentAccessService;
use App\Modules\Content\Services\BunnyStreamService;
use App\Modules\Content\Services\LessonService;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class CertificatesAndMediaTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

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

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        $this->unit = $this->subject->units()->firstOrFail();

        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);
    }

    public function test_passing_exam_issues_certificate_with_verification_code(): void
    {
        $question = app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => '2+2',
            'points' => 10,
            'options' => [
                ['label' => '4', 'is_correct' => true],
                ['label' => '5', 'is_correct' => false],
            ],
        ]);

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'امتحان الشهادة',
            'pass_score' => 50,
            'question_ids' => [$question->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);

        $attempts = app(ExamAttemptService::class);
        $attempt = $attempts->start($this->student, $exam);
        $attempts->autosaveAnswer($this->student, $attempt, $question, [
            'selected_option_id' => $question->options->firstWhere('is_correct', true)->id,
        ]);
        $attempts->submit($this->student, $attempt->fresh());

        $certificate = Certificate::query()->where('student_id', $this->student->id)->first();

        $this->assertNotNull($certificate);
        $this->assertNotEmpty($certificate->verification_code);
        $this->assertSame($exam->id, $certificate->exam_id);

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('شهادة صحيحة', false)
            ->assertSee($this->student->name, false);
    }

    public function test_failing_exam_does_not_issue_certificate(): void
    {
        $question = app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => '2+2',
            'points' => 10,
            'options' => [
                ['label' => '4', 'is_correct' => true],
                ['label' => '5', 'is_correct' => false],
            ],
        ]);

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'امتحان صعب',
            'pass_score' => 80,
            'question_ids' => [$question->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);

        $attempts = app(ExamAttemptService::class);
        $attempt = $attempts->start($this->student, $exam);
        $attempts->autosaveAnswer($this->student, $attempt, $question, [
            'selected_option_id' => $question->options->firstWhere('is_correct', false)->id,
        ]);
        $attempts->submit($this->student, $attempt->fresh());

        $this->assertDatabaseMissing('certificates', [
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_student_can_view_own_certificate_page(): void
    {
        $certificate = Certificate::query()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'title' => 'شهادة تجريبية',
            'verification_code' => 'CERT-TESTCODE1',
            'score' => 90,
            'max_score' => 100,
            'issued_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('شهادة تجريبية', false)
            ->assertSee('CERT-TESTCODE1', false);
    }

    public function test_bunny_rejects_invalid_video_id_and_caps_ttl(): void
    {
        config([
            'bunny.library_id' => 'lib-1',
            'bunny.token_auth_key' => 'secret',
            'bunny.token_ttl_seconds' => 99999,
            'bunny.max_token_ttl_seconds' => 120,
            'bunny.embed_base_url' => 'https://iframe.mediadelivery.net/embed',
        ]);

        $bunny = app(BunnyStreamService::class);
        $url = $bunny->embedUrl('vid-ok');

        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertLessThanOrEqual(now()->timestamp + 120, (int) $query['expires']);
        $this->assertGreaterThanOrEqual(now()->timestamp + 60, (int) $query['expires']);

        $this->expectException(\InvalidArgumentException::class);
        $bunny->embedUrl('../evil');
    }

    public function test_bunny_requires_config_when_flag_enabled(): void
    {
        config([
            'bunny.library_id' => null,
            'bunny.token_auth_key' => null,
            'bunny.require_config' => true,
        ]);

        $this->expectException(RuntimeException::class);
        app(BunnyStreamService::class)->embedUrl('vid-1');
    }

    public function test_attachment_download_requires_valid_signature_and_access(): void
    {
        Storage::fake('public');

        $lesson = app(LessonService::class)->create($this->teacher, $this->unit, [
            'title' => 'مع مرفق',
            'type' => LessonType::Text->value,
            'is_published' => true,
        ]);

        $file = UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf');
        $attachment = app(LessonService::class)->addAttachment($this->teacher, $lesson, $file, true);

        $signed = app(AttachmentAccessService::class)->signedDownloadUrl($this->student, $attachment);

        $this->actingAs($this->student)
            ->get($signed)
            ->assertOk();

        $this->actingAs($this->student)
            ->get(route('student.attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_certificate_service_finds_by_code(): void
    {
        $certificate = Certificate::query()->create([
            'student_id' => $this->student->id,
            'title' => 'تحقق',
            'verification_code' => 'CERT-ABCDEF12',
            'issued_at' => now(),
        ]);

        $found = app(CertificateService::class)->findByVerificationCode('cert-abcdef12');

        $this->assertNotNull($found);
        $this->assertSame($certificate->id, $found->id);
    }
}
