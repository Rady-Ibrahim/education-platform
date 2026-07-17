<?php

namespace Tests\Feature;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class ExamsModuleTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private QuestionBankService $questions;

    private ExamService $exams;

    private ExamAttemptService $attempts;

    private User $teacher;

    private User $student;

    private User $outsider;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $this->questions = app(QuestionBankService::class);
        $this->exams = app(ExamService::class);
        $this->attempts = app(ExamAttemptService::class);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->outsider = User::factory()->create();
        $this->outsider->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);
    }

    public function test_teacher_can_create_mcq_question(): void
    {
        $question = $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => '2 + 2 = ؟',
            'points' => 2,
            'options' => [
                ['label' => '3', 'is_correct' => false],
                ['label' => '4', 'is_correct' => true],
                ['label' => '5', 'is_correct' => false],
            ],
        ]);

        $this->assertSame(QuestionType::Mcq, $question->type);
        $this->assertCount(3, $question->options);
        $this->assertSame(1, $question->options->where('is_correct', true)->count());
    }

    public function test_mcq_requires_exactly_one_correct_option(): void
    {
        $this->expectException(ValidationException::class);

        $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => 'سؤال خاطئ',
            'options' => [
                ['label' => 'أ', 'is_correct' => true],
                ['label' => 'ب', 'is_correct' => true],
            ],
        ]);
    }

    public function test_teacher_can_create_exam_with_questions_and_publish(): void
    {
        $q1 = $this->makeMcq('س1', 'صح');
        $q2 = $this->makeFill('عاصمة مصر', 'القاهرة');

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'امتحان تجريبي',
            'duration_minutes' => 20,
            'max_attempts' => 2,
            'question_ids' => [$q1->id, $q2->id],
            'is_published' => true,
        ]);

        $this->assertTrue($exam->is_published);
        $this->assertCount(2, $exam->questions);
    }

    public function test_cannot_publish_exam_without_questions(): void
    {
        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'فارغ',
            'is_published' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->exams->publish($this->teacher, $exam, true);
    }

    public function test_student_can_start_attempt_and_outsider_cannot(): void
    {
        $exam = $this->publishedExam();

        $attempt = $this->attempts->start($this->student, $exam);
        $this->assertSame(ExamAttemptStatus::InProgress, $attempt->status);

        $this->expectException(ValidationException::class);
        $this->attempts->start($this->outsider, $exam);
    }

    public function test_autosave_persists_answer_immediately(): void
    {
        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);
        $question = $exam->questions->first();
        $correctOption = $question->options->firstWhere('is_correct', true);

        $answer = $this->attempts->autosaveAnswer($this->student, $attempt, $question, [
            'selected_option_id' => $correctOption->id,
        ]);

        $this->assertDatabaseHas('exam_answers', [
            'id' => $answer->id,
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
        ]);
        $this->assertNotNull($answer->saved_at);

        // تحديث نفس الإجابة (autosave مرة تانية)
        $other = $question->options->firstWhere('is_correct', false);
        $updated = $this->attempts->autosaveAnswer($this->student, $attempt, $question, [
            'selected_option_id' => $other->id,
        ]);

        $this->assertSame($answer->id, $updated->id);
        $this->assertSame($other->id, $updated->selected_option_id);
    }

    public function test_submit_auto_grades_objective_questions(): void
    {
        $mcq = $this->makeMcq('2+2', '4');
        $fill = $this->makeFill('عاصمة مصر', 'القاهرة');
        $essay = $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::Essay->value,
            'stem' => 'اشرح',
            'points' => 5,
        ]);

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'تصحيح',
            'max_attempts' => 1,
            'question_ids' => [$mcq->id, $fill->id, $essay->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);

        $this->attempts->autosaveAnswer($this->student, $attempt, $mcq, [
            'selected_option_id' => $mcq->options->firstWhere('is_correct', true)->id,
        ]);
        $this->attempts->autosaveAnswer($this->student, $attempt, $fill, [
            'answer_text' => 'القاهرة',
        ]);
        $this->attempts->autosaveAnswer($this->student, $attempt, $essay, [
            'answer_text' => 'إجابة مقالية',
        ]);

        $submitted = $this->attempts->submit($this->student, $attempt->fresh());

        $this->assertSame(ExamAttemptStatus::Graded, $submitted->status);
        $this->assertEquals(2.0, (float) $submitted->score); // mcq 1 + fill 1, essay not auto
        $this->assertEquals(7.0, (float) $submitted->max_score);
    }

    public function test_teacher_can_manually_grade_essay(): void
    {
        $essay = $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::Essay->value,
            'stem' => 'مقالي',
            'points' => 10,
        ]);

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'مقالي فقط',
            'question_ids' => [$essay->id],
            'is_published' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);
        $this->attempts->autosaveAnswer($this->student, $attempt, $essay, [
            'answer_text' => 'نصي',
        ]);
        $this->attempts->submit($this->student, $attempt->fresh());

        $this->attempts->gradeEssay($this->teacher, $attempt->fresh(), $essay, 8);

        $this->assertEquals(8.0, (float) $attempt->fresh()->score);
    }

    public function test_student_payload_does_not_leak_correct_answers(): void
    {
        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);
        $payload = $this->attempts->questionsForStudent($attempt);

        $json = json_encode($payload);
        $this->assertStringNotContainsString('is_correct', $json);
        $this->assertStringNotContainsString('correct_answer', $json);
        $this->assertArrayHasKey('stem', $payload[0]);
        $this->assertArrayHasKey('options', $payload[0]);
    }

    public function test_max_attempts_is_enforced(): void
    {
        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'محاولة واحدة',
            'max_attempts' => 1,
            'question_ids' => [$this->makeMcq('س', 'أ')->id],
            'is_published' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);
        $this->attempts->submit($this->student, $attempt);

        $this->expectException(ValidationException::class);
        $this->attempts->start($this->student, $exam);
    }

    public function test_resume_returns_same_in_progress_attempt(): void
    {
        $exam = $this->publishedExam();
        $first = $this->attempts->start($this->student, $exam);
        $second = $this->attempts->start($this->student, $exam);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('student_id', $this->student->id)->count());
    }

    public function test_exam_pages_are_role_protected(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.exams'))->assertOk();
        $this->actingAs($this->student)->get(route('student.exams'))->assertOk();
        $this->actingAs($this->student)->get(route('teacher.exams'))->assertForbidden();
    }

    public function test_exam_can_be_created_with_schedule_window(): void
    {
        $starts = now()->addHour();
        $ends = now()->addDays(2);

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'امتحان مجدول',
            'starts_at' => $starts,
            'ends_at' => $ends,
            'pass_score' => 60,
            'question_ids' => [$this->makeMcq('س', 'أ')->id],
            'is_published' => true,
        ]);

        $this->assertNotNull($exam->starts_at);
        $this->assertNotNull($exam->ends_at);
        $this->assertEquals(60.0, (float) $exam->pass_score);
        $this->assertFalse($exam->isAvailableNow());
    }

    private function makeMcq(string $stem, string $correctLabel): Question
    {
        return $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => $stem,
            'points' => 1,
            'options' => [
                ['label' => $correctLabel, 'is_correct' => true],
                ['label' => 'خطأ', 'is_correct' => false],
            ],
        ]);
    }

    private function makeFill(string $stem, string $answer): Question
    {
        return $this->questions->create($this->teacher, $this->subject, [
            'type' => QuestionType::FillBlank->value,
            'stem' => $stem,
            'points' => 1,
            'correct_answer' => $answer,
        ]);
    }

    private function publishedExam()
    {
        return $this->exams->create($this->teacher, $this->subject, [
            'title' => 'امتحان',
            'duration_minutes' => 30,
            'max_attempts' => 3,
            'question_ids' => [$this->makeMcq('سؤال', 'إجابة')->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);
    }
}
