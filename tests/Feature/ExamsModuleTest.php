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
use App\Notifications\ExamResultNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_question_order_is_stable_per_attempt(): void
    {
        $ids = [];
        for ($i = 1; $i <= 6; $i++) {
            $ids[] = $this->makeMcq("س{$i}", "ج{$i}")->id;
        }

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'ترتيب',
            'duration_minutes' => 30,
            'question_ids' => $ids,
            'is_published' => true,
            'shuffle_questions' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);

        $first = collect($this->attempts->questionsForStudent($attempt))->pluck('id');
        $second = collect($this->attempts->questionsForStudent($attempt))->pluck('id');

        $this->assertSame($first->all(), $second->all());
        $this->assertCount(6, $first->unique());
    }

    public function test_remaining_seconds_counts_down_from_server_time(): void
    {
        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);

        $initial = $this->attempts->remainingSeconds($attempt);
        $this->assertNotNull($initial);
        $this->assertGreaterThan(1700, $initial);
        $this->assertLessThanOrEqual(1800, $initial);

        $this->travel(10)->minutes();

        $later = $this->attempts->remainingSeconds($attempt->fresh());
        $this->assertGreaterThan(1100, $later);
        $this->assertLessThanOrEqual(1200, $later);

        // بدون مؤقت
        $untimedExam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'بدون وقت',
            'question_ids' => [$this->makeMcq('س', 'أ')->id],
            'is_published' => true,
        ]);
        $untimedAttempt = $this->attempts->start($this->student, $untimedExam);
        $this->assertNull($this->attempts->remainingSeconds($untimedAttempt));
    }

    public function test_expired_timer_blocks_autosave_and_submits_automatically(): void
    {
        Notification::fake();

        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);
        $question = $exam->questions->first();

        $this->travel(31)->minutes();

        try {
            $this->attempts->autosaveAnswer($this->student, $attempt, $question, [
                'selected_option_id' => $question->options->first()->id,
            ]);
            $this->fail('يجب منع الحفظ بعد انتهاء الوقت.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('انتهى وقت', $e->errors()['attempt'][0] ?? '');
        }

        $attempt = $attempt->fresh();
        $this->assertSame(ExamAttemptStatus::Graded, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);

        Notification::assertSentTo($this->student, ExamResultNotification::class);
    }

    public function test_auto_submit_command_finalizes_expired_attempts(): void
    {
        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);

        $this->travel(45)->minutes();

        $this->artisan('exams:auto-submit-expired')->expectsOutputToContain('Auto-submitted 1');

        $this->assertSame(ExamAttemptStatus::Graded, $attempt->fresh()->status);
    }

    public function test_duplicate_submission_is_noop(): void
    {
        $exam = $this->publishedExam();
        $attempt = $this->attempts->start($this->student, $exam);

        $first = $this->attempts->submit($this->student, $attempt);
        $submittedAt = $first->submitted_at->toAtomString();
        $score = (float) $first->score;

        $second = $this->attempts->submit($this->student, $attempt->fresh());

        $this->assertSame($submittedAt, $second->submitted_at->toAtomString());
        $this->assertSame($score, (float) $second->score);
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('student_id', $this->student->id)->count());
    }

    public function test_student_cannot_touch_another_students_attempt(): void
    {
        $exam = $this->publishedExam();
        $ownerAttempt = $this->attempts->start($this->student, $exam);

        $this->expectException(ValidationException::class);
        $this->attempts->autosaveAnswer($this->outsider, $ownerAttempt, $exam->questions->first(), [
            'answer_text' => 'اختراق',
        ]);
    }

    public function test_outsider_cannot_submit_someone_elses_attempt(): void
    {
        $exam = $this->publishedExam();
        $ownerAttempt = $this->attempts->start($this->student, $exam);

        $this->expectException(ValidationException::class);
        $this->attempts->submit($this->outsider, $ownerAttempt);
    }

    public function test_unpublished_exam_cannot_be_started(): void
    {
        $draft = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'مسودة',
            'question_ids' => [$this->makeMcq('س', 'أ')->id],
            'is_published' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->attempts->start($this->student, $draft);
    }

    public function test_other_teacher_cannot_manage_foreign_exam(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole(UserRole::Teacher);

        $otherSubject = Subject::query()->where('id', '!=', $this->subject->id)->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($otherTeacher, $otherSubject);

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'أمتحان المعلم الأول',
            'question_ids' => [$this->makeMcq('س', 'أ')->id],
            'is_published' => true,
        ]);

        $foreignQuestion = $this->questions->create($otherTeacher, $otherSubject, [
            'type' => QuestionType::TrueFalse->value,
            'stem' => 'سؤال أجنبي',
            'correct_answer' => 'true',
        ]);

        // نشر/إلغاء نشر امتحان غيره
        try {
            $this->exams->publish($otherTeacher, $exam, false);
            $this->fail('يجب منع إلغاء النشر.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // مزامنة أسئلة من مادة أخرى
        try {
            $this->exams->syncQuestions($otherTeacher, $exam, [$foreignQuestion->id]);
            $this->fail('يجب منع مزامنة أسئلة مادة أخرى.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // تحديث / حذف امتحان غيره
        try {
            $this->exams->update($otherTeacher, $exam, ['title' => 'هاجم']);
            $this->fail('يجب منع التعديل.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        try {
            $this->exams->delete($otherTeacher, $exam);
            $this->fail('يجب منع الحذف.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        $this->assertTrue($exam->fresh()->is_published);
    }

    public function test_teacher_can_update_and_delete_own_exam(): void
    {
        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'قابل للتعديل',
            'duration_minutes' => 20,
            'question_ids' => [$this->makeMcq('س1', 'ج1')->id],
            'is_published' => true,
        ]);

        $updated = $this->exams->update($this->teacher, $exam, [
            'title' => 'معدّل',
            'description' => 'وصف جديد',
            'duration_minutes' => 40,
            'pass_score' => 50.0,
        ]);

        $this->assertSame('معدّل', $updated->title);
        $this->assertSame('وصف جديد', $updated->description);
        $this->assertSame(40, $updated->duration_minutes);

        // حذف مع محاولة جارية → تُسلَّم تلقائيًا ثم soft delete
        $attempt = $this->attempts->start($this->student, $updated);
        $this->attempts->autosaveAnswer($this->student, $attempt, $updated->questions->first(), [
            'selected_option_id' => $updated->questions->first()->options->firstWhere('is_correct', true)->id,
        ]);

        $this->exams->delete($this->teacher, $updated);

        $this->assertSoftDeleted('exams', ['id' => $updated->id]);
        $this->assertSame(ExamAttemptStatus::Graded, $attempt->fresh()->status);
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id]);
    }

    public function test_teacher_can_update_and_delete_question(): void
    {
        $question = $this->makeMcq('قبل التعديل', 'خطأ');

        $updated = $this->questions->update($this->teacher, $question, [
            'stem' => 'بعد التعديل',
            'points' => 3,
            'correct_answer' => null,
            'options' => [
                ['label' => 'الصحيحة الجديدة', 'is_correct' => true],
                ['label' => 'خاطئة', 'is_correct' => false],
                ['label' => 'خاطئة 2', 'is_correct' => false],
            ],
        ]);

        $this->assertSame('بعد التعديل', $updated->stem);
        $this->assertCount(3, $updated->options);
        $this->assertSame(1, $updated->options->where('is_correct', true)->count());

        // حذف سؤال مرفق بامتحان → ممنوع
        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'مرتبط',
            'question_ids' => [$updated->id],
            'is_published' => false,
        ]);

        try {
            $this->questions->delete($this->teacher, $updated);
            $this->fail('يجب منع حذف سؤال مرفق بامتحان.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // سؤال غير مرفق → يُحذف
        $free = $this->makeMcq('حر', 'أ');
        $this->questions->delete($this->teacher, $free);
        $this->assertSoftDeleted('questions', ['id' => $free->id]);
    }

    public function test_result_summary_counts_and_pass_state(): void
    {
        $mcqCorrect = $this->makeMcq('صحيح', 'نعم');
        $mcqWrong = $this->makeMcq('خطأ', 'الصحيحة');
        $mcqBlank = $this->makeMcq('متروك', 'أي شيء');

        $exam = $this->exams->create($this->teacher, $this->subject, [
            'title' => 'ملخص',
            'duration_minutes' => 30,
            'pass_score' => 50,
            'question_ids' => [$mcqCorrect->id, $mcqWrong->id, $mcqBlank->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);

        $this->attempts->autosaveAnswer($this->student, $attempt, $mcqCorrect, [
            'selected_option_id' => $mcqCorrect->options->firstWhere('is_correct', true)->id,
        ]);
        $this->attempts->autosaveAnswer($this->student, $attempt, $mcqWrong, [
            'selected_option_id' => $mcqWrong->options->firstWhere('is_correct', false)->id,
        ]);

        $graded = $this->attempts->submit($this->student, $attempt);
        $summary = $this->attempts->resultSummary($graded);

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['answered']);
        $this->assertSame(1, $summary['correct']);
        $this->assertSame(1, $summary['wrong']);
        $this->assertSame(1, $summary['unanswered']);
        $this->assertEquals(33.33, $summary['percent']);
        $this->assertFalse($summary['passed']);

        // نفس الملخص يعمل للمدرس وولي الأمر عبر المحاولة المحفوظة
        $this->assertSame($summary, $this->attempts->resultSummary($graded->fresh()));
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
