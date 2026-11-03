<?php

namespace Tests\Feature;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Models\LessonAttachment;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Content\Services\LessonService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Services\TeacherJoinService;
use App\Modules\Identity\Services\TeacherStudentService;
use App\Modules\Identity\Services\UserApprovalService;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\MonthlyCollectionService;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

/**
 * Full end-to-end flow test for the three main roles.
 *
 * Scenarios covered:
 *  A. Admin: approve teacher → verify teacher can login
 *  B. Teacher: create lesson (text, live), create exam, add student, collect payment
 *  C. Student: join request approved → access lesson → take exam
 *  D. Attachment: upload, download guard, delete cleans disk
 *  E. Payment proof: stored on private disk, not web-accessible
 *  F. Avatar: stored on public disk, old file removed on update
 */
class FullFlowE2ETest extends TestCase
{
    use GrantsSubscriptionAccess, RefreshDatabase;

    private User $admin;
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

        // ── Admin ──────────────────────────────────────────────────────────
        $this->admin = User::factory()->create(['status' => UserStatus::Active]);
        $this->admin->assignRole(UserRole::Admin);

        // ── Teacher (starts PendingAdmin) ─────────────────────────────────
        $this->teacher = User::factory()->create(['status' => UserStatus::PendingAdmin]);
        $this->teacher->assignRole(UserRole::Teacher);

        $this->subject = Subject::query()->first();
        $this->unit    = $this->subject->units()->first();
        $this->teacher->teachingSubjects()->sync([$this->subject->id]);
        app(PlatformBillingService::class)->ensureSubscription($this->teacher);

        // ── Student (starts PendingTeacher) ───────────────────────────────
        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $this->student = User::factory()->create(['status' => UserStatus::PendingTeacher]);
        $this->student->assignRole(UserRole::Student);
        $this->student->grades()->sync([$grade->id => ['enrolled_at' => now()]]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // A. ADMIN FLOW
    // ══════════════════════════════════════════════════════════════════════

    public function test_A1_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_A2_admin_can_see_pending_teacher(): void
    {
        $this->assertSame(UserStatus::PendingAdmin, $this->teacher->status);

        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_A3_admin_approves_teacher_and_teacher_becomes_active(): void
    {
        $approval = app(UserApprovalService::class);
        $approval->approve($this->teacher, $this->admin);

        $this->assertSame(UserStatus::Active, $this->teacher->fresh()->status);
        $this->assertNotNull($this->teacher->fresh()->approved_at);
    }

    public function test_A4_pending_teacher_cannot_access_dashboard(): void
    {
        // Teacher is still PendingAdmin — must be redirected to account.pending
        $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('account.pending'));
    }

    public function test_A5_approved_teacher_can_access_dashboard(): void
    {
        app(UserApprovalService::class)->approve($this->teacher, $this->admin);

        $this->actingAs($this->teacher->fresh())
            ->get(route('teacher.dashboard'))
            ->assertOk();
    }

    // ══════════════════════════════════════════════════════════════════════
    // B. TEACHER FLOW
    // ══════════════════════════════════════════════════════════════════════

    private function approvedTeacher(): User
    {
        if ($this->teacher->fresh()->status !== UserStatus::Active) {
            app(UserApprovalService::class)->approve($this->teacher, $this->admin);
        }
        return $this->teacher->fresh();
    }

    public function test_B1_teacher_lessons_page_loads(): void
    {
        $teacher = $this->approvedTeacher();

        $this->actingAs($teacher)
            ->get(route('teacher.lessons'))
            ->assertOk();
    }

    public function test_B2_teacher_creates_text_lesson(): void
    {
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson = $service->create($teacher, $this->unit, [
            'title'        => 'درس تجريبي نصي',
            'type'         => 'text',
            'body'         => 'محتوى الدرس التجريبي',
            'is_published' => false,
        ]);

        $this->assertDatabaseHas('lessons', [
            'id'         => $lesson->id,
            'title'      => 'درس تجريبي نصي',
            'type'       => 'text',
            'is_published' => false,
        ]);
    }

    public function test_B3_teacher_creates_live_lesson_with_meeting_url(): void
    {
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson = $service->create($teacher, $this->unit, [
            'title'        => 'حصة لايف',
            'type'         => 'live',
            'meeting_url'  => 'https://meet.google.com/abc-defg-hij',
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'is_published' => true,
        ]);

        $this->assertSame('https://meet.google.com/abc-defg-hij', $lesson->meeting_url);
        $this->assertNotNull($lesson->scheduled_at);
        $this->assertNull($lesson->bunny_video_id); // live lessons never have a video id
    }

    public function test_B4_live_lesson_requires_meeting_url(): void
    {
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->create($teacher, $this->unit, [
            'title' => 'حصة بدون رابط',
            'type'  => 'live',
            // no meeting_url
        ]);
    }

    public function test_B5_teacher_publishes_lesson(): void
    {
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson = $service->create($teacher, $this->unit, [
            'title' => 'درس مسودة',
            'type'  => 'text',
            'body'  => 'محتوى',
        ]);

        $this->assertFalse($lesson->is_published);

        $service->publish($teacher, $lesson, true);
        $this->assertTrue($lesson->fresh()->is_published);
    }

    public function test_B6_teacher_uploads_attachment_and_it_lands_on_public_disk(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson = $service->create($teacher, $this->unit, ['title' => 'درس مع مرفق', 'type' => 'text']);

        $file = UploadedFile::fake()->create('notes.pdf', 512, 'application/pdf');
        $attachment = $service->addAttachment($teacher, $lesson, $file, true);

        $this->assertDatabaseHas('lesson_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_B7_teacher_deletes_attachment_removes_file_from_disk(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson     = $service->create($teacher, $this->unit, ['title' => 'درس', 'type' => 'text']);
        $file       = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $attachment = $service->addAttachment($teacher, $lesson, $file);
        $path       = $attachment->path;

        Storage::disk('public')->assertExists($path);

        $service->deleteAttachment($teacher, $attachment);

        Storage::disk('public')->assertMissing($path);
        // SoftDeletes: record still exists in DB but with deleted_at set
        $this->assertNotNull(
            \App\Modules\Content\Models\LessonAttachment::withTrashed()->find($attachment->id)?->deleted_at,
            'Attachment should be soft-deleted'
        );
    }

    public function test_B8_deleting_lesson_removes_attachment_files(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();
        $service = app(LessonService::class);

        $lesson     = $service->create($teacher, $this->unit, ['title' => 'درس', 'type' => 'text']);
        $file       = UploadedFile::fake()->create('file.pdf', 100, 'application/pdf');
        $attachment = $service->addAttachment($teacher, $lesson, $file);
        $path       = $attachment->path;

        Storage::disk('public')->assertExists($path);

        $service->delete($teacher, $lesson);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_B9_teacher_creates_question_and_exam(): void
    {
        $teacher  = $this->approvedTeacher();
        $qService = app(QuestionBankService::class);
        $eService = app(ExamService::class);

        $q = $qService->create($teacher, $this->subject, [
            'type'   => 'mcq',
            'stem'   => 'ما هي عاصمة مصر؟',
            'points' => 1,
            'options' => [
                ['label' => 'القاهرة', 'is_correct' => true],
                ['label' => 'الإسكندرية', 'is_correct' => false],
            ],
        ]);

        $exam = $eService->create($teacher, $this->subject, [
            'title'           => 'امتحان تجريبي',
            'duration_minutes' => 10,
            'max_attempts'    => 1,
            'question_ids'    => [$q->id],
            'shuffle_questions' => true,
            'is_published'    => true,
        ]);

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'shuffle_questions' => true]);
        $this->assertSame(1, $exam->questions()->count());
    }

    public function test_B10_teacher_creates_student_account_directly(): void
    {
        $teacher = $this->approvedTeacher();
        $grade   = Grade::query()->where('code', 'S1')->firstOrFail();

        $result  = app(TeacherStudentService::class)->createStudent($teacher, [
            'name'     => 'طالب يدوي',
            'email'    => 'manual.student@test.com',
            'phone'    => '01099999999',
            'grade_id' => $grade->id,
        ]);

        $student = $result['user'];
        $this->assertTrue($student->isActive());
        $this->assertTrue($teacher->students()->where('users.id', $student->id)->exists());
        $this->assertNotEmpty($result['plain_password']);
    }

    public function test_B11_teacher_approves_student_join_request_and_activates_account(): void
    {
        $teacher = $this->approvedTeacher();

        // Student sends join request (PendingTeacher status)
        $joinService = app(TeacherJoinService::class);
        $request     = $joinService->requestJoin($this->student, $teacher, 'عايز أنضم');

        $this->assertSame(JoinRequestStatus::Pending, $request->status);
        $this->assertSame(UserStatus::PendingTeacher, $this->student->fresh()->status);

        // Teacher approves → student becomes Active
        $joinService->approve($request, $teacher);

        $this->assertSame(UserStatus::Active, $this->student->fresh()->status);
        $this->assertTrue($teacher->students()->where('users.id', $this->student->id)->exists());
    }

    public function test_B12_teacher_payment_collection_flow(): void
    {
        $teacher  = $this->approvedTeacher();

        // Enroll student manually (teacher creates them)
        $grade   = Grade::query()->where('code', 'S1')->firstOrFail();
        $result  = app(TeacherStudentService::class)->createStudent($teacher, [
            'name'     => 'طالب للتحصيل',
            'email'    => 'pay.student@test.com',
            'grade_id' => $grade->id,
        ]);
        $student = $result['user'];

        // Create subscription plan and enroll
        $plan = app(SubscriptionPlanService::class)->create($teacher, $this->subject, [
            'name'          => 'اشتراك شهري',
            'price'         => 300,
            'duration_days' => 30,
        ]);

        $subscription = app(EnrollmentService::class)->enrollStudentByTeacher($teacher, $student, $plan);

        // Generate month dues
        $collection = app(MonthlyCollectionService::class);
        $dues = $collection->generateMonthDues($teacher);

        $charge = $dues->firstWhere('student_id', $student->id);
        $this->assertNotNull($charge, 'Should have a charge for the student this month');

        // Collect cash
        $payment = $collection->collectCash($teacher, $charge, ['notes' => 'دفعة كاش تجريبية']);

        $this->assertDatabaseHas('payments', [
            'id'         => $payment->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // C. STUDENT FLOW
    // ══════════════════════════════════════════════════════════════════════

    private function activeStudent(): User
    {
        $teacher = $this->approvedTeacher();
        $join    = app(TeacherJoinService::class)->requestJoin($this->student, $teacher);
        app(TeacherJoinService::class)->approve($join, $teacher);
        $this->grantActiveSubscription($this->student, $teacher, $this->subject);
        return $this->student->fresh();
    }

    public function test_C1_student_dashboard_loads_after_approval(): void
    {
        $student = $this->activeStudent();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_C2_student_cannot_access_dashboard_while_pending(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('account.pending'));
    }

    public function test_C3_student_can_access_published_lesson(): void
    {
        $teacher = $this->approvedTeacher();

        // Create and publish a lesson
        $lesson = app(LessonService::class)->create($teacher, $this->unit, [
            'title'        => 'درس للطالب',
            'type'         => 'text',
            'body'         => 'محتوى الدرس',
            'is_published' => true,
        ]);

        $student = $this->activeStudent();
        $access  = app(ContentAccessService::class);

        $this->assertTrue($access->studentCanAccessLesson($student, $lesson));
    }

    public function test_C4_student_cannot_access_unpublished_lesson(): void
    {
        $teacher = $this->approvedTeacher();

        $lesson = app(LessonService::class)->create($teacher, $this->unit, [
            'title'        => 'درس مسودة',
            'type'         => 'text',
            'is_published' => false,
        ]);

        $student = $this->activeStudent();
        $access  = app(ContentAccessService::class);

        $this->assertFalse($access->studentCanAccessLesson($student, $lesson));
    }

    public function test_C5_student_lessons_page_loads(): void
    {
        $student = $this->activeStudent();

        $this->actingAs($student)
            ->get(route('student.lessons'))
            ->assertOk();
    }

    public function test_C6_student_can_start_and_submit_exam(): void
    {
        $teacher  = $this->approvedTeacher();
        $student  = $this->activeStudent();

        $q = app(QuestionBankService::class)->create($teacher, $this->subject, [
            'type'   => 'mcq',
            'stem'   => 'ما هو ناتج 2+2؟',
            'points' => 1,
            'options' => [
                ['label' => '4', 'is_correct' => true],
                ['label' => '5', 'is_correct' => false],
            ],
        ]);

        $exam = app(ExamService::class)->create($teacher, $this->subject, [
            'title'            => 'امتحان الطالب',
            'duration_minutes' => 5,
            'max_attempts'     => 1,
            'question_ids'     => [$q->id],
            'is_published'     => true,
        ]);

        $attempts = app(ExamAttemptService::class);
        $attempt  = $attempts->start($student, $exam);

        $this->assertNotNull($attempt->id);

        // Answer and submit
        $attempts->autosaveAnswer($student, $attempt, $q, [
            'selected_option_id' => $q->options->firstWhere('is_correct', true)->id,
        ]);

        $result = $attempts->submit($student, $attempt);

        $this->assertNotNull($result->score);
        $this->assertSame(1.0, (float) $result->score);
    }

    public function test_C7_student_exams_page_loads(): void
    {
        $student = $this->activeStudent();

        $this->actingAs($student)
            ->get(route('student.exams'))
            ->assertOk();
    }

    // ══════════════════════════════════════════════════════════════════════
    // D. ATTACHMENT SECURITY
    // ══════════════════════════════════════════════════════════════════════

    public function test_D1_attachment_download_requires_auth_and_valid_signature(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();

        $lesson     = app(LessonService::class)->create($teacher, $this->unit, [
            'title'        => 'درس مع مرفق',
            'type'         => 'text',
            'is_published' => true,
        ]);
        $file       = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');
        $attachment = app(LessonService::class)->addAttachment($teacher, $lesson, $file, true);

        // Unauthenticated → redirect to login (not 403, because auth middleware fires first)
        $this->get(route('student.attachments.download', $attachment))
            ->assertRedirect(route('login'));

        // Authenticated but no valid signature → 403
        $student = $this->activeStudent();
        $this->actingAs($student)
            ->get(route('student.attachments.download', $attachment))
            ->assertStatus(403);
    }

    public function test_D2_student_without_subscription_cannot_download(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();

        $lesson     = app(LessonService::class)->create($teacher, $this->unit, [
            'title'        => 'درس للتحقق',
            'type'         => 'text',
            'is_published' => true,
        ]);
        $file       = UploadedFile::fake()->create('file.pdf', 50, 'application/pdf');
        $attachment = app(LessonService::class)->addAttachment($teacher, $lesson, $file, true);

        // Student has no subscription
        $grade = Grade::query()->where('code', 'S1')->firstOrFail();
        $noSubStudent = User::factory()->create(['status' => UserStatus::Active]);
        $noSubStudent->assignRole(UserRole::Student);

        $access = app(\App\Modules\Content\Services\AttachmentAccessService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $access->signedDownloadUrl($noSubStudent, $attachment);
    }

    // ══════════════════════════════════════════════════════════════════════
    // E. PAYMENT PROOF SECURITY (Private Disk)
    // ══════════════════════════════════════════════════════════════════════

    public function test_E1_payment_proof_stored_on_private_disk(): void
    {
        Storage::fake('private');
        $teacher = $this->approvedTeacher();

        // Enroll student
        $grade   = Grade::query()->where('code', 'S1')->firstOrFail();
        $result  = app(TeacherStudentService::class)->createStudent($teacher, [
            'name'     => 'طالب دفع',
            'email'    => 'pay2.student@test.com',
            'grade_id' => $grade->id,
        ]);
        $student = $result['user'];

        $plan = app(SubscriptionPlanService::class)->create($teacher, $this->subject, [
            'name'  => 'خطة شهرية',
            'price' => 250,
        ]);
        $sub = app(EnrollmentService::class)->enrollStudentByTeacher($teacher, $student, $plan);

        // Submit Vodafone proof (parent flow)
        $proof = UploadedFile::fake()->image('receipt.jpg');

        // Temporarily enable student vodafone for this test
        config(['payments.student_vodafone_enabled' => true]);

        $payment = app(\App\Modules\Payments\Services\PaymentRecordService::class)
            ->submitVodafoneProof($student, $sub, [
                'amount'             => 250,
                'external_reference' => 'VF-TEST-001',
            ], $proof);

        // Proof must be on private disk
        $this->assertNotEmpty($payment->proof_path);
        Storage::disk('private')->assertExists($payment->proof_path);

        // Must NOT be accessible via public storage URL
        $this->assertStringNotContainsString('/storage/', $payment->proof_path ?? '');
    }

    // ══════════════════════════════════════════════════════════════════════
    // F. AVATAR UPLOAD
    // ══════════════════════════════════════════════════════════════════════

    public function test_F1_teacher_avatar_stored_on_public_disk(): void
    {
        Storage::fake('public');
        $teacher = $this->approvedTeacher();

        $avatar = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $updated = app(\App\Modules\Identity\Services\TeacherProfileService::class)
            ->updateAvatar($teacher, $avatar);

        $this->assertNotNull($updated->avatar_path);
        Storage::disk('public')->assertExists($updated->avatar_path);
        $this->assertStringStartsWith('teacher-avatars/', $updated->avatar_path);
    }

    public function test_F2_uploading_new_avatar_removes_old_file(): void
    {
        Storage::fake('public');
        $teacher  = $this->approvedTeacher();
        $profiles = app(\App\Modules\Identity\Services\TeacherProfileService::class);

        $first  = UploadedFile::fake()->image('first.jpg');
        $second = UploadedFile::fake()->image('second.jpg');

        $after1 = $profiles->updateAvatar($teacher, $first);
        $path1  = $after1->avatar_path;

        Storage::disk('public')->assertExists($path1);

        $after2 = $profiles->updateAvatar($after1, $second);

        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($after2->avatar_path);
    }

    // ══════════════════════════════════════════════════════════════════════
    // G. PAGES LOAD — smoke test all key routes
    // ══════════════════════════════════════════════════════════════════════

    public function test_G_all_teacher_pages_load_without_500(): void
    {
        $teacher = $this->approvedTeacher();

        $routes = [
            route('teacher.dashboard'),
            route('teacher.groups'),
            route('teacher.attendance'),
            route('teacher.students'),
            route('teacher.lessons'),
            route('teacher.exams'),
            route('teacher.payments'),
            route('teacher.messages'),
            route('profile'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($teacher)
                ->get($url)
                ->assertOk("Route $url returned non-200");
        }
    }

    public function test_G_all_student_pages_load_without_500(): void
    {
        $student = $this->activeStudent();

        $routes = [
            route('student.dashboard'),
            route('student.lessons'),
            route('student.exams'),
            route('student.subscriptions'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($student)
                ->get($url)
                ->assertOk("Route $url returned non-200");
        }
    }

    public function test_G_all_admin_pages_load_without_500(): void
    {
        $routes = [
            route('admin.dashboard'),
            route('admin.users'),
            route('admin.academic'),
            route('admin.payments'),
            route('admin.platform'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk("Route $url returned non-200");
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // H. CROSS-ROLE ACCESS ENFORCEMENT
    // ══════════════════════════════════════════════════════════════════════

    public function test_H1_student_cannot_access_teacher_routes(): void
    {
        $student = $this->activeStudent();

        // Spatie roles return 403 (not a redirect) when role doesn't match
        $this->actingAs($student)
            ->get(route('teacher.dashboard'))
            ->assertStatus(403);
    }

    public function test_H2_teacher_cannot_access_admin_routes(): void
    {
        $teacher = $this->approvedTeacher();

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_H3_teacher_cannot_edit_another_teachers_lesson(): void
    {
        $teacher1 = $this->approvedTeacher();

        $teacher2 = User::factory()->create(['status' => UserStatus::Active]);
        $teacher2->assignRole(UserRole::Teacher);

        $lesson = app(LessonService::class)->create($teacher1, $this->unit, [
            'title' => 'درس المدرس الأول',
            'type'  => 'text',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(LessonService::class)->update($teacher2, $lesson, ['title' => 'اختراق!']);
    }

    public function test_H4_unauthenticated_redirected_to_login(): void
    {
        $this->get(route('teacher.dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
