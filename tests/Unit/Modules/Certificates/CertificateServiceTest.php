<?php

namespace Tests\Unit\Modules\Certificates;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Certificates\Services\CertificateService;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private Subject $subject;

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
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);
    }

    public function test_attempt_passes_at_exact_threshold(): void
    {
        $question = app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => 'س',
            'points' => 100,
            'options' => [
                ['label' => 'صح', 'is_correct' => true],
                ['label' => 'خطأ', 'is_correct' => false],
            ],
        ]);

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'عتبة',
            'pass_score' => 50,
            'question_ids' => [$question->id],
            'is_published' => true,
        ]);

        $attempt = ExamAttempt::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'status' => ExamAttemptStatus::Graded,
            'started_at' => now(),
            'submitted_at' => now(),
            'score' => 50,
            'max_score' => 100,
        ]);

        $this->assertTrue(app(CertificateService::class)->attemptPasses($attempt));
    }

    public function test_issuing_certificate_twice_is_idempotent(): void
    {
        $question = app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => 'س',
            'points' => 10,
            'options' => [
                ['label' => 'صح', 'is_correct' => true],
                ['label' => 'خطأ', 'is_correct' => false],
            ],
        ]);

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'شهادة',
            'pass_score' => 50,
            'question_ids' => [$question->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($this->student, $exam);
        $service->autosaveAnswer($this->student, $attempt, $question, [
            'selected_option_id' => $question->options->firstWhere('is_correct', true)->id,
        ]);
        $graded = $service->submit($this->student, $attempt->fresh());

        $first = app(CertificateService::class)->issueForPassedAttempt($graded);
        $second = app(CertificateService::class)->issueForPassedAttempt($graded);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('certificates', 1);
    }
}
