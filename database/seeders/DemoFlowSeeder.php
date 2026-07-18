<?php

namespace Database\Seeders;

use App\Enums\JoinRequestStatus;
use App\Enums\LessonType;
use App\Enums\ParentLinkStatus;
use App\Enums\ParentRelationship;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Services\LessonService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Services\StudentCodeService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Illuminate\Database\Seeder;

class DemoFlowSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::defaultBranch();
        $gradeS1 = Grade::query()->where('code', 'S1')->firstOrFail();
        $gradeS2 = Grade::query()->where('code', 'S2')->firstOrFail();

        $subjectS1 = $this->ensureProgrammingSubject($gradeS1, 1);
        $subjectS2 = $this->ensureProgrammingSubject($gradeS2, 2);

        $teacher = User::query()->updateOrCreate(
            ['email' => 'rady.ibrahim@education.test'],
            [
                'name' => 'راضي إبراهيم',
                'slug' => 'rady-ibrahim',
                'headline' => 'مدرس برمجة — أولى وتانية ثانوي',
                'bio' => "شرح عملي للبرمجة من الصفر مع مشاريع وتطبيقات.\nصفوف: الأول والثاني الثانوي.",
                'phone' => '01020000001',
                'vodafone_cash_number' => '01020000001',
                'payment_instructions' => "الدفع نهاية الشهر: كاش في السنتر أو فودافون كاش على الرقم أعلاه.\nبعد التحويل أرسل صورة الإثبات من لوحة ولي الأمر.",
                'is_publicly_visible' => true,
                'branch_id' => $branch?->id,
                'password' => 'password',
                'status' => UserStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $teacher->syncRoles([UserRole::Teacher->value]);
        $teacher->teachingSubjects()->sync([$subjectS1->id, $subjectS2->id]);
        $teacher = $teacher->fresh();
        app(PlatformBillingService::class)->ensureSubscription($teacher);

        $plans = app(SubscriptionPlanService::class);
        $planS1 = $teacher->subscriptionPlans()->where('subject_id', $subjectS1->id)->first()
            ?? $plans->create($teacher, $subjectS1, [
                'name' => 'اشتراك شهري — برمجة أولى ثانوي',
                'price' => 400,
                'duration_days' => 30,
                'description' => 'دروس ومشاريع البرمجة للصف الأول الثانوي',
            ]);
        $planS2 = $teacher->subscriptionPlans()->where('subject_id', $subjectS2->id)->first()
            ?? $plans->create($teacher, $subjectS2, [
                'name' => 'اشتراك شهري — برمجة تانية ثانوي',
                'price' => 450,
                'duration_days' => 30,
                'description' => 'دروس ومشاريع البرمجة للصف الثاني الثانوي',
            ]);

        $unitS1 = Unit::query()->where('subject_id', $subjectS1->id)->orderBy('ordering')->firstOrFail();
        $lessons = app(LessonService::class);

        if (! $unitS1->lessons()->where('title', 'مقدمة في البرمجة')->exists()) {
            $lessons->create($teacher, $unitS1, [
                'title' => 'مقدمة في البرمجة',
                'type' => LessonType::Text->value,
                'body' => "مرحبا بك في كورس البرمجة.\nهنتعلم الأساسيات خطوة بخطوة مع أمثلة عملية.",
                'is_published' => true,
            ]);
        }

        if (! $unitS1->lessons()->where('title', 'حصة لايف — أسئلة ومراجعة')->exists()) {
            $lessons->create($teacher, $unitS1, [
                'title' => 'حصة لايف — أسئلة ومراجعة',
                'type' => LessonType::Live->value,
                'body' => 'انضم للحصة في الموعد المحدد.',
                'meeting_url' => 'https://zoom.us/j/123456789',
                'scheduled_at' => now()->addDays(2)->setTime(19, 0),
                'is_published' => true,
            ]);
        }

        $questions = app(QuestionBankService::class);
        $exams = app(ExamService::class);
        $mcq = Question::query()
            ->where('subject_id', $subjectS1->id)
            ->where('stem', 'ما هي لغة البرمجة؟')
            ->first()
            ?? $questions->create($teacher, $subjectS1, [
                'type' => QuestionType::Mcq->value,
                'stem' => 'ما هي لغة البرمجة؟',
                'points' => 2,
                'options' => [
                    ['label' => 'طريقة للتواصل مع الحاسوب عبر تعليمات', 'is_correct' => true],
                    ['label' => 'نوع من الأجهزة', 'is_correct' => false],
                    ['label' => 'نظام تشغيل فقط', 'is_correct' => false],
                ],
            ]);

        if (! Exam::query()->where('subject_id', $subjectS1->id)->where('title', 'اختبار قصير — أساسيات')->exists()) {
            $exams->create($teacher, $subjectS1, [
                'title' => 'اختبار قصير — أساسيات',
                'duration_minutes' => 15,
                'max_attempts' => 2,
                'pass_score' => 50,
                'question_ids' => [$mcq->id],
                'is_published' => true,
            ]);
        }

        $studentActive = $this->makeStudent(
            email: 'student.active@education.test',
            name: 'أحمد محمد',
            phone: '01030000001',
            grade: $gradeS1,
            branchId: $branch?->id,
        );

        $teacher->students()->syncWithoutDetaching([
            $studentActive->id => ['joined_at' => now()],
        ]);

        $enrollment = app(EnrollmentService::class);
        $subscription = Subscription::query()
            ->where('student_id', $studentActive->id)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subjectS1->id)
            ->first();

        if (! $subscription) {
            $subscription = $enrollment->enrollStudentByTeacher($teacher, $studentActive, $planS1);
            $enrollment->activate($subscription);
            Payment::query()->create([
                'student_id' => $studentActive->id,
                'teacher_id' => $teacher->id,
                'subscription_id' => $subscription->id,
                'channel' => PaymentChannel::Cash,
                'amount' => $planS1->price,
                'status' => PaymentStatus::Confirmed,
                'recorded_by' => $teacher->id,
                'reviewed_by' => $teacher->id,
                'reviewed_at' => now(),
                'notes' => 'دفعة كاش تجريبية من السيدر',
            ]);
        }

        $studentPending = $this->makeStudent(
            email: 'student.pending@education.test',
            name: 'سارة علي',
            phone: '01030000002',
            grade: $gradeS2,
            branchId: $branch?->id,
        );

        TeacherJoinRequest::query()->updateOrCreate(
            [
                'student_id' => $studentPending->id,
                'teacher_id' => $teacher->id,
            ],
            [
                'status' => JoinRequestStatus::Pending,
                'message' => 'عايزة أنضم لمجموعة البرمجة تانية ثانوي',
            ]
        );

        $parent = User::query()->updateOrCreate(
            ['email' => 'parent@education.test'],
            [
                'name' => 'محمود والد أحمد',
                'phone' => '01030000003',
                'branch_id' => $branch?->id,
                'password' => 'password',
                'status' => UserStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
            ]
        );
        $parent->syncRoles([UserRole::Parent->value]);

        ParentStudentLink::query()->updateOrCreate(
            [
                'parent_id' => $parent->id,
                'student_id' => $studentActive->id,
            ],
            [
                'status' => ParentLinkStatus::Active,
                'relationship' => ParentRelationship::Father,
                'linked_by' => $teacher->id,
                'approved_by' => $teacher->id,
                'approved_at' => now(),
                'message' => 'ربط تجريبي من السيدر',
            ]
        );

        if (! Payment::query()
            ->where('student_id', $studentActive->id)
            ->where('channel', PaymentChannel::VodafoneCash)
            ->where('status', PaymentStatus::PendingReview)
            ->exists()) {
            Payment::query()->create([
                'student_id' => $studentActive->id,
                'teacher_id' => $teacher->id,
                'subscription_id' => $subscription->id,
                'channel' => PaymentChannel::VodafoneCash,
                'amount' => $planS1->price,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $parent->id,
                'external_reference' => 'VF-DEMO-001',
                'notes' => 'تحويل تجريبي بانتظار التأكيد',
            ]);
        }

        $this->command?->info('Demo ready: راضي إبراهيم + طلاب + ولي أمر + دروس/امتحان.');
    }

    private function ensureProgrammingSubject(Grade $grade, int $ordering): Subject
    {
        $subject = Subject::query()->updateOrCreate(
            [
                'grade_id' => $grade->id,
                'code' => 'PROG',
            ],
            [
                'name' => 'البرمجة',
                'ordering' => $ordering,
                'is_active' => true,
                'is_custom' => true,
            ]
        );

        Unit::query()->updateOrCreate(
            [
                'subject_id' => $subject->id,
                'name' => 'الوحدة الأولى — أساسيات',
            ],
            [
                'ordering' => 1,
                'is_active' => true,
            ]
        );

        return $subject;
    }

    private function makeStudent(string $email, string $name, string $phone, Grade $grade, ?int $branchId): User
    {
        $student = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'branch_id' => $branchId,
                'password' => 'password',
                'status' => UserStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'student_code' => User::query()->where('email', $email)->value('student_code')
                    ?: app(StudentCodeService::class)->generate(),
            ]
        );
        $student->syncRoles([UserRole::Student->value]);
        $student->grades()->sync([
            $grade->id => ['enrolled_at' => now()],
        ]);

        return $student->fresh();
    }
}
