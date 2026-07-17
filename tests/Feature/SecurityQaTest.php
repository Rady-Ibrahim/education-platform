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
use App\Modules\Content\Services\LessonPlaybackService;
use App\Modules\Content\Services\LessonService;
use App\Modules\Exams\Models\ExamAnswer;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use App\Modules\Payments\Models\Payment;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class SecurityQaTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private User $otherStudent;

    private Subject $subject;

    private Unit $unit;

    private ExamAttemptService $attempts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $this->attempts = app(ExamAttemptService::class);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create();
        $this->student->assignRole(UserRole::Student);

        $this->otherStudent = User::factory()->create();
        $this->otherStudent->assignRole(UserRole::Student);

        $this->subject = Subject::query()->firstOrFail();
        $this->unit = $this->subject->units()->firstOrFail();

        app(AcademicStructureService::class)->assignTeacherToSubject($this->teacher, $this->subject);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);
    }

    public function test_autosave_survives_repeated_disconnect_and_resume(): void
    {
        $q1 = $this->makeMcq('س1', 'أ');
        $q2 = $this->makeMcq('س2', 'ب');

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'انقطاع',
            'question_ids' => [$q1->id, $q2->id],
            'is_published' => true,
            'shuffle_questions' => false,
            'max_attempts' => 2,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);

        $this->attempts->autosaveAnswer($this->student, $attempt, $q1, [
            'selected_option_id' => $q1->options->firstWhere('is_correct', true)->id,
        ]);

        // محاكاة انقطاع: طلب جديد يستأنف نفس المحاولة
        $resumed = $this->attempts->start($this->student, $exam);
        $this->assertSame($attempt->id, $resumed->id);

        $this->attempts->autosaveAnswer($this->student, $resumed, $q2, [
            'selected_option_id' => $q2->options->firstWhere('is_correct', true)->id,
        ]);

        // انقطاع ثاني ثم استئناف
        $again = $this->attempts->start($this->student, $exam);
        $this->assertSame($attempt->id, $again->id);
        $this->assertSame(2, ExamAnswer::query()->where('attempt_id', $again->id)->count());

        $payload = $this->attempts->questionsForStudent($again);
        $this->assertArrayNotHasKey('correct_answer', $payload[0]);
        $this->assertFalse(collect($payload[0]['options'] ?? [])->contains(fn ($o) => array_key_exists('is_correct', $o)));
    }

    public function test_student_cannot_autosave_or_submit_another_students_attempt(): void
    {
        $this->teacher->students()->attach($this->otherStudent->id, ['joined_at' => now()]);
        $this->grantActiveSubscription($this->otherStudent, $this->teacher, $this->subject);

        $question = $this->makeMcq('س', 'أ');
        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'عزل',
            'question_ids' => [$question->id],
            'is_published' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);

        $this->expectException(ValidationException::class);
        $this->attempts->autosaveAnswer($this->otherStudent, $attempt, $question, [
            'selected_option_id' => $question->options->first()->id,
        ]);
    }

    public function test_unauthorized_student_cannot_get_bunny_embed_url(): void
    {
        config([
            'bunny.library_id' => 'lib-1',
            'bunny.token_auth_key' => 'secret-key',
            'bunny.embed_base_url' => 'https://iframe.mediadelivery.net/embed',
        ]);

        $lesson = app(LessonService::class)->create($this->teacher, $this->unit, [
            'title' => 'فيديو محمي',
            'type' => LessonType::Video->value,
            'bunny_video_id' => 'secret-vid',
            'is_published' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(LessonPlaybackService::class)->signedEmbedUrl($this->otherStudent, $lesson);
    }

    public function test_student_cannot_view_another_students_certificate(): void
    {
        $certificate = Certificate::query()->create([
            'student_id' => $this->student->id,
            'title' => 'شهادة خاصة',
            'verification_code' => 'CERT-PRIVATE01',
            'issued_at' => now(),
        ]);

        $this->actingAs($this->otherStudent)
            ->get(route('student.certificates.show', $certificate))
            ->assertForbidden();
    }

    public function test_autosave_rate_limit_blocks_burst(): void
    {
        config([
            'exams.autosave_rate_limit' => 3,
            'exams.autosave_rate_decay_seconds' => 60,
        ]);

        RateLimiter::clear('exam-autosave:'.$this->student->id);

        $question = $this->makeMcq('س', 'أ');
        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'حد',
            'question_ids' => [$question->id],
            'is_published' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);
        $optionId = $question->options->first()->id;

        $this->attempts->autosaveAnswer($this->student, $attempt, $question, ['selected_option_id' => $optionId]);
        $this->attempts->autosaveAnswer($this->student, $attempt, $question, ['selected_option_id' => $optionId]);
        $this->attempts->autosaveAnswer($this->student, $attempt, $question, ['selected_option_id' => $optionId]);

        $this->expectException(ValidationException::class);
        $this->attempts->autosaveAnswer($this->student, $attempt, $question, ['selected_option_id' => $optionId]);
    }

    public function test_payment_policy_scopes_teacher_view(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole(UserRole::Teacher);

        $payment = Payment::query()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'channel' => \App\Enums\PaymentChannel::Cash,
            'amount' => 50,
            'status' => \App\Enums\PaymentStatus::PendingReview,
            'recorded_by' => $this->teacher->id,
        ]);

        $this->assertTrue($this->teacher->can('view', $payment));
        $this->assertFalse($otherTeacher->can('view', $payment));
        $this->assertTrue($this->teacher->can('review', $payment));
        $this->assertFalse($otherTeacher->can('review', $payment));
    }

    public function test_parent_dashboard_is_accessible(): void
    {
        $parent = User::factory()->create();
        $parent->assignRole(UserRole::Parent);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk();
    }

    private function makeMcq(string $stem, string $correctLabel)
    {
        return app(QuestionBankService::class)->create($this->teacher, $this->subject, [
            'type' => QuestionType::Mcq->value,
            'stem' => $stem,
            'points' => 1,
            'options' => [
                ['label' => $correctLabel, 'is_correct' => true],
                ['label' => 'بديل', 'is_correct' => false],
            ],
        ]);
    }
}
