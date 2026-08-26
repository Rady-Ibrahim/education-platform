<?php

namespace Tests\Feature;

use App\Enums\ExamAttemptStatus;
use App\Enums\ParentLinkStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Notifications\ExamResultNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class ParentExamAccessAndNotificationsTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private User $activeParent;

    private User $pendingParent;

    private User $unlinkedParent;

    private Subject $subject;

    private ExamAttemptService $attempts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\BranchSeeder::class);
        $this->seed(\Database\Seeders\AcademicStructureSeeder::class);

        $this->teacher = User::factory()->create(['status' => UserStatus::Active]);
        $this->teacher->assignRole(UserRole::Teacher);

        $this->student = User::factory()->create(['status' => UserStatus::Active]);
        $this->student->assignRole(UserRole::Student);

        $this->activeParent = User::factory()->create(['status' => UserStatus::Active]);
        $this->activeParent->assignRole(UserRole::Parent);

        $this->pendingParent = User::factory()->create(['status' => UserStatus::Active]);
        $this->pendingParent->assignRole(UserRole::Parent);

        $this->unlinkedParent = User::factory()->create(['status' => UserStatus::Active]);
        $this->unlinkedParent->assignRole(UserRole::Parent);

        $grade = Grade::query()->firstOrFail();
        $this->subject = Subject::query()->create([
            'grade_id' => $grade->id,
            'name' => 'العلوم',
            'code' => 'SCI_PARENT',
            'ordering' => 97,
            'is_active' => true,
        ]);

        $this->teacher->teachingSubjects()->attach($this->subject->id);
        $this->teacher->students()->attach($this->student->id, ['joined_at' => now()]);

        ParentStudentLink::query()->create([
            'parent_id' => $this->activeParent->id,
            'student_id' => $this->student->id,
            'status' => ParentLinkStatus::Active,
        ]);

        ParentStudentLink::query()->create([
            'parent_id' => $this->pendingParent->id,
            'student_id' => $this->student->id,
            'status' => ParentLinkStatus::Pending,
        ]);

        $this->attempts = app(ExamAttemptService::class);
    }

    private function makePublishedExam(): Exam
    {
        /** @var QuestionBankService $bank */
        $bank = app(QuestionBankService::class);

        $question = $bank->create($this->teacher, $this->subject, [
            'type' => 'mcq',
            'stem' => 'س: 2 + 2 ؟',
            'points' => 1,
            'options' => [
                ['label' => '4', 'is_correct' => true],
                ['label' => '5', 'is_correct' => false],
            ],
        ]);

        return app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'امتحان أولياء الأمور',
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'question_ids' => [$question->id],
            'is_published' => true,
            'shuffle_questions' => false,
        ]);
    }

    private function gradedAttempt(Exam $exam): void
    {
        $attempt = $this->attempts->start($this->student, $exam);
        $correct = $exam->questions->first()->options->firstWhere('is_correct', true);
        $this->attempts->autosaveAnswer($this->student, $attempt, $exam->questions->first(), [
            'option_id' => $correct->id,
        ]);
        $this->attempts->submit($this->student, $attempt->fresh());
    }

    public function test_parent_overview_shows_only_linked_children_results(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam();
        $this->gradedAttempt($exam);

        // ولي الأمر المرتبط يرى النتيجة
        $this->actingAs($this->activeParent)
            ->get(route('parent.exams'))
            ->assertOk()
            ->assertSee('امتحان أولياء الأمور')
            ->assertSee($this->student->name);

        // ولي الأمر بطلب ربط معلّق لا يرى شيئًا
        $this->actingAs($this->pendingParent)
            ->get(route('parent.exams'))
            ->assertOk()
            ->assertDontSee('امتحان أولياء الأمور');

        // ولي الأمر غير المرتبط لا يرى شيئًا
        $this->actingAs($this->unlinkedParent)
            ->get(route('parent.exams'))
            ->assertOk()
            ->assertDontSee('امتحان أولياء الأمور');
    }

    public function test_child_exams_page_allows_linked_and_forbids_unlinked_parent(): void
    {
        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam();
        $this->gradedAttempt($exam);

        $this->actingAs($this->activeParent)
            ->get(route('parent.children.exams', $this->student))
            ->assertOk()
            ->assertSee('امتحان أولياء الأمور');

        $this->actingAs($this->unlinkedParent)
            ->get(route('parent.children.exams', $this->student))
            ->assertForbidden();
    }

    public function test_non_parents_cannot_access_parent_exam_pages(): void
    {
        $this->actingAs($this->student)
            ->get(route('parent.exams'))
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->get(route('parent.exams'))
            ->assertForbidden();
    }

    public function test_submit_notifies_student_and_active_parents_only(): void
    {
        Notification::fake();

        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        $exam = $this->makePublishedExam();
        $this->gradedAttempt($exam);

        // الطالب يتلقى الإشعار
        Notification::assertSentTo($this->student, ExamResultNotification::class);

        // ولي الأمر النشِط يتلقاه بجمهور parent
        Notification::assertSentTo($this->activeParent, ExamResultNotification::class, function (ExamResultNotification $notification) {
            return $notification->audience === 'parent';
        });

        // ولي الأمر بطلب معلّق وغير المرتبط لا يتلقيان شيئًا
        Notification::assertNotSentTo($this->pendingParent, ExamResultNotification::class);
        Notification::assertNotSentTo($this->unlinkedParent, ExamResultNotification::class);
    }

    public function test_manual_essay_grading_notifies_as_well(): void
    {
        Notification::fake();

        $this->grantActiveSubscription($this->student, $this->teacher, $this->subject);

        /** @var QuestionBankService $bank */
        $bank = app(QuestionBankService::class);

        $essay = $bank->create($this->teacher, $this->subject, [
            'type' => \App\Enums\QuestionType::Essay->value,
            'stem' => 'اكتب فقرة عن الماء',
            'points' => 10,
        ]);

        $exam = app(ExamService::class)->create($this->teacher, $this->subject, [
            'title' => 'مقالي للتنبيهات',
            'question_ids' => [$essay->id],
            'is_published' => true,
        ]);

        $attempt = $this->attempts->start($this->student, $exam);
        $this->attempts->autosaveAnswer($this->student, $attempt, $essay, [
            'answer_text' => 'الماء ضروري للحياة.',
        ]);
        $this->attempts->submit($this->student, $attempt->fresh());

        // التسليم يصنّف تلقائيًا (المقالي يُضاف لاحقًا يدويًا) والإشعارات أُرسلت
        $this->assertSame(ExamAttemptStatus::Graded, $attempt->fresh()->status);
        Notification::assertSentTo($this->activeParent, ExamResultNotification::class);

        // التصحيح اليدوي يعيد الإشعار بالنتيجة النهائية
        Notification::fake();
        $this->attempts->gradeEssay($this->teacher, $attempt->fresh(), $essay, 8);

        $this->assertSame(ExamAttemptStatus::Graded, $attempt->fresh()->status);
        Notification::assertSentTo($this->student, ExamResultNotification::class);
        Notification::assertSentTo($this->activeParent, ExamResultNotification::class);
        Notification::assertNotSentTo($this->pendingParent, ExamResultNotification::class);
    }
}
