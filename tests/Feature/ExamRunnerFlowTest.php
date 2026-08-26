<?php

namespace Tests\Feature;

use App\Enums\ParentLinkStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Policies\ExamAttemptPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class ExamRunnerFlowTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $otherTeacher;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\BranchSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $this->teacher = User::factory()->create(['status' => UserStatus::Active]);
        $this->teacher->assignRole(UserRole::Teacher);

        $this->otherTeacher = User::factory()->create(['status' => UserStatus::Active]);
        $this->otherTeacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create(['status' => UserStatus::Active]);
        $this->student->assignRole(UserRole::Student);

        $grade = Grade::query()->firstOrFail();
        $this->subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'الرياضيات',
            'code' => 'MATH_FLOW',
            'ordering' => 99,
            'is_active' => true,
        ]);

        $this->teacher->teachingSubjects()->attach($this->subject->id);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    private function makePublishedExam(int $questionsCount = 2): Exam
    {
        /** @var QuestionBankService $bank */
        $bank = app(\App\Modules\Exams\Services\QuestionBankService::class);
        $ids = [];

        for ($i = 1; $i <= $questionsCount; $i++) {
            $ids[] = $bank->create($this->teacher, $this->subject, [
                'type' => 'mcq',
                'stem' => "س{$i}: كم يساوي {$i} + {$i} ؟",
                'points' => 1,
                'options' => [
                    ['label' => (string) ($i * 2), 'is_correct' => true],
                    ['label' => (string) ($i * 3), 'is_correct' => false],
                ],
            ])->id;
        }

        return app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'امتحان الفلو',
            'description' => 'امتحان تجربة شامل',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'question_ids' => $ids,
            'is_published' => true,
            'shuffle_questions' => false,
        ]);
    }

    public function test_student_complete_exam_flow_with_review_and_submit(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam(3);

        $this->actingAs($this->student);

        $component = Livewire::test(\App\Livewire\Student\TakeExam::class);

        // قائمة الامتحانات تعرض الامتحان المنشور فقط
        $component->assertSee('امتحان الفلو');

        // معلومات الامتحان ثم البدء
        $component->call('previewExam', $exam->id)
            ->assertSet('previewExamId', $exam->id)
            ->call('startExam', $exam->id)
            ->assertSet('attemptId', function ($value) {
                return ! is_null($value);
            })
            ->assertCount('questions', 3)
            ->assertSet('currentIndex', 0)
            ->assertSet('hasTimer', true);

        /** @var array<int, array{id: int}> $questions */
        $questions = $component->instance()->questions;
        [$q1, $q2, $q3] = $questions;

        // الإجابة على السؤال الأول صحيحة
        $correct1 = \App\Modules\Exams\Models\Question::query()->find($q1['id'])->options->firstWhere('is_correct', true);
        $component->call('saveOption', $q1['id'], $correct1->id);

        // التنقل التالي/السابق مع الاحتفاظ بالإجابة
        $component->call('next')->assertSet('currentIndex', 1);
        $component->call('previous')->assertSet('currentIndex', 0);
        $this->assertSame(
            $correct1->id,
            $component->instance()->answers[$q1['id']]['option_id']
        );

        // مراجعة ثم تسليم نهائي
        $component->call('next');
        $component->call('next');
        $component->call('openReview')->assertSet('showReview', true);
        $component->call('goTo', 0)->assertSet('showReview', true);
        $component->call('submitExam');

        $instance = $component->instance();
        $this->assertNotNull($instance->resultScore);
        $this->assertEquals(1.0, (float) $instance->resultScore);
        $this->assertEquals(3.0, (float) $instance->resultMax);
        $this->assertNull($instance->resultPassed); // درجة نجاح غير محددة ⇒ بدون حكم نجاح/رسوب

        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->sole();
        $this->assertSame(\App\Enums\ExamAttemptStatus::Graded, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);

        // منع التعديل بعد التسليم
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Modules\Exams\Services\ExamAttemptService::class)->autosaveAnswer(
            $this->student,
            $attempt,
            \App\Modules\Exams\Models\Question::query()->find($q2['id']),
            ['answer_text' => 'محاولة تعديل']
        );
    }

    public function test_refresh_restores_in_progress_attempt_and_saved_answer(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam();

        // الجلسة الأولى: بدء وحفظ إجابة
        $this->actingAs($this->student);

        $first = Livewire::test(\App\Livewire\Student\TakeExam::class);
        $first->call('startExam', $exam->id);

        /** @var array<int, array{id: int}> $questions */
        $questions = $first->instance()->questions;
        $option = \App\Modules\Exams\Models\Question::query()->find($questions[0]['id'])->options->first();
        $first->call('saveOption', $questions[0]['id'], $option->id);

        $attemptId = $first->instance()->attemptId;

        // «Refresh»: مثيل جديد يستكمل نفس المحاولة والإجابة والوقت المتبقي
        $second = Livewire::test(\App\Livewire\Student\TakeExam::class);

        $this->assertSame($attemptId, $second->instance()->attemptId);
        $this->assertSame($option->id, $second->instance()->answers[$questions[0]['id']]['option_id']);
        $this->assertGreaterThan(0, $second->instance()->remainingSeconds);
        $this->assertCount(2, $second->instance()->questions);

        // نفس المحاولة في قاعدة البيانات (لا محاولة مكررة)
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('student_id', $this->student->id)->count());
    }

    public function test_timer_expiry_triggers_auto_submit(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam();

        $this->actingAs($this->student);

        $component = Livewire::test(\App\Livewire\Student\TakeExam::class);
        $component->call('startExam', $exam->id);

        $this->travel(31)->minutes();

        $component->call('autoSubmit');

        $this->assertNotNull($component->instance()->resultScore);
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('status', '!=', 'in_progress')->count());
    }

    public function test_unpublished_exam_is_hidden_from_student_page(): void
    {
        $draft = $this->makePublishedExam();
        $draft->update(['is_published' => false]);

        $this->actingAs($this->student)
            ->get(route('student.exams'))
            ->assertOk()
            ->assertDontSee('امتحان الفلو');
    }

    public function test_teacher_exam_results_route_authorization(): void
    {
        $exam = $this->makePublishedExam();

        // المالك
        $this->actingAs($this->teacher)
            ->get(route('teacher.exams.results', $exam))
            ->assertOk();

        // مدرس آخر لنفس الدور لكن غير مالك المادة
        $otherSubject = Subject::query()->create([
            'grade_id' => Grade::query()->firstOrFail()->id,
            'name' => 'أخرى',
            'code' => 'OTHER_FLOW',
            'ordering' => 98,
            'is_active' => true,
        ]);
        $this->otherTeacher->teachingSubjects()->attach($otherSubject->id);

        $this->actingAs($this->otherTeacher)
            ->get(route('teacher.exams.results', $exam))
            ->assertForbidden();

        // طالب → role middleware يمنع
        $this->actingAs($this->student)
            ->get(route('teacher.exams.results', $exam))
            ->assertForbidden();
    }

    public function test_exam_attempt_policy_scopes_roles_correctly(): void
    {
        $exam = $this->makePublishedExam();
        $attempt = ExamAttempt::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'status' => \App\Enums\ExamAttemptStatus::Graded,
            'started_at' => now(),
            'submitted_at' => now(),
            'score' => 1,
            'max_score' => 2,
        ]);

        $policy = new ExamAttemptPolicy;

        // الطالب يرى محاولته فقط
        $this->assertTrue($policy->view($this->student, $attempt));

        $outsider = User::factory()->create(['status' => UserStatus::Active]);
        $outsider->assignRole(UserRole::Student);
        $this->assertFalse($policy->view($outsider, $attempt));

        // المعلم المالك يراها، ومدرس آخر لا
        $this->assertTrue($policy->view($this->teacher, $attempt));
        $this->assertFalse($policy->view($this->otherTeacher, $attempt));

        // ولي الأمر المرتبط يراها، وغير مرتبط لا
        $linkedParent = User::factory()->create(['status' => UserStatus::Active]);
        $linkedParent->assignRole(UserRole::Parent);
        ParentStudentLink::query()->create([
            'parent_id' => $linkedParent->id,
            'student_id' => $this->student->id,
            'status' => ParentLinkStatus::Active,
        ]);

        $unrelatedParent = User::factory()->create(['status' => UserStatus::Active]);
        $unrelatedParent->assignRole(UserRole::Parent);

        $this->assertTrue($policy->view($linkedParent, $attempt));
        $this->assertFalse($policy->view($unrelatedParent, $attempt));
    }
}
