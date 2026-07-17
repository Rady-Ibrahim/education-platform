<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeacherCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::defaultBranch();
        $plans = app(SubscriptionPlanService::class);
        $billing = app(PlatformBillingService::class);

        $teachers = [
            [
                'name' => 'أحمد محمود',
                'email' => 'ahmed.math@education.test',
                'slug' => 'ahmed-mahmoud',
                'headline' => 'مدرس رياضيات — ثانوية عامة',
                'bio' => "شرح مبسّط للمنهج مع تدريبات أسبوعية ومراجعات قبل الامتحان.\nخبرة أكثر من 12 سنة في الثانوية العامة.",
                'phone' => '01010000001',
                'vodafone' => '01010000001',
                'cover' => 'images/teachers/ahmed-math.jpg',
                'subject_code' => 'MATH',
                'grade_code' => 'S3',
                'price' => 450,
            ],
            [
                'name' => 'سارة حسن',
                'email' => 'sara.arabic@education.test',
                'slug' => 'sara-hassan',
                'headline' => 'مدرسة لغة عربية — ثانوية عامة',
                'bio' => "نحو وبلاغة ونصوص بأسلوب واضح، مع نماذج إجابات وتصحيح دوري.\nتركيز على درجات التعبير والقراءة.",
                'phone' => '01010000002',
                'vodafone' => '01010000002',
                'cover' => 'images/teachers/sara-arabic.jpg',
                'subject_code' => 'AR',
                'grade_code' => 'S3',
                'price' => 400,
            ],
            [
                'name' => 'عمر خالد',
                'email' => 'omar.english@education.test',
                'slug' => 'omar-khaled',
                'headline' => 'مدرس لغة إنجليزية — ثانوية عامة',
                'bio' => "Grammar وReading وWriting بمنهج عملي وتمارين مستمرة.\nحصص أونلاين مسجّلة للمراجعة في أي وقت.",
                'phone' => '01010000003',
                'vodafone' => '01010000003',
                'cover' => 'images/teachers/omar-english.jpg',
                'subject_code' => 'EN',
                'grade_code' => 'S2',
                'price' => 420,
            ],
            [
                'name' => 'نورة إبراهيم',
                'email' => 'noura.physics@education.test',
                'slug' => 'noura-ibrahim',
                'headline' => 'مدرسة فيزياء — أولى ثانوي',
                'bio' => "شرح المفاهيم بالتجارب والأمثلة اليومية، مع ملخصات جاهزة للامتحان.\nمتابعة فردية للطلاب الضعاف في الحساب.",
                'phone' => '01010000004',
                'vodafone' => '01010000004',
                'cover' => 'images/teachers/noura-physics.jpg',
                'subject_code' => 'PHY',
                'subject_name' => 'الفيزياء',
                'grade_code' => 'S1',
                'price' => 480,
            ],
            [
                'name' => 'يوسف عادل',
                'email' => 'youssef.chem@education.test',
                'slug' => 'youssef-adel',
                'headline' => 'مدرس كيمياء — ثانوية عامة',
                'bio' => "معادلات وتفاعلات بطريقة مرتّبة، مع بنك أسئلة على كل باب.\nمراجعات مركّزة قبل امتحانات الشهر.",
                'phone' => '01010000005',
                'vodafone' => '01010000005',
                'cover' => 'images/teachers/youssef-chem.jpg',
                'subject_code' => 'CHEM',
                'subject_name' => 'الكيمياء',
                'grade_code' => 'S2',
                'price' => 470,
            ],
            [
                'name' => 'مها فتحي',
                'email' => 'maha.biology@education.test',
                'slug' => 'maha-fathy',
                'headline' => 'مدرسة أحياء — ثانوية عامة',
                'bio' => "شرح مفصّل مع رسوم توضيحية وخرائط ذهنية لكل باب.\nامتحانات قصيرة أسبوعية لقياس المستوى.",
                'phone' => '01010000006',
                'vodafone' => '01010000006',
                'cover' => 'images/teachers/maha-biology.jpg',
                'subject_code' => 'BIO',
                'subject_name' => 'الأحياء',
                'grade_code' => 'S3',
                'price' => 460,
            ],
        ];

        foreach ($teachers as $data) {
            $subject = $this->resolveSubject(
                $data['grade_code'],
                $data['subject_code'],
                $data['subject_name'] ?? null,
            );

            if (! $subject) {
                $this->command?->warn("تخطي {$data['name']}: المادة غير موجودة.");

                continue;
            }

            $teacher = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'headline' => $data['headline'],
                    'bio' => $data['bio'],
                    'avatar_path' => $data['cover'],
                    'cover_path' => $data['cover'],
                    'phone' => $data['phone'],
                    'vodafone_cash_number' => $data['vodafone'],
                    'payment_instructions' => "حوّل على فودافون كاش ثم أرسل صورة التحويل من لوحة ولي الأمر.\nأو ادفع كاش في السنتر نهاية الشهر.",
                    'is_publicly_visible' => true,
                    'branch_id' => $branch?->id,
                    'password' => 'password',
                    'status' => UserStatus::Active,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                ]
            );

            $teacher->syncRoles([UserRole::Teacher->value]);
            $teacher->teachingSubjects()->sync([$subject->id]);

            $planName = 'اشتراك شهري — '.$subject->name;
            $existingPlan = $teacher->subscriptionPlans()
                ->where('subject_id', $subject->id)
                ->where('name', $planName)
                ->first();

            if (! $existingPlan) {
                $plans->create($teacher, $subject, [
                    'name' => $planName,
                    'price' => $data['price'],
                    'duration_days' => 30,
                    'description' => 'وصول كامل لدروس ومراجعات '.$subject->name,
                ]);
            }

            $billing->ensureSubscription($teacher->fresh());
        }
    }

    private function resolveSubject(string $gradeCode, string $subjectCode, ?string $subjectName): ?Subject
    {
        $grade = Grade::query()->where('code', $gradeCode)->first();
        if (! $grade) {
            return null;
        }

        $subject = Subject::query()->updateOrCreate(
            [
                'grade_id' => $grade->id,
                'code' => $subjectCode,
            ],
            [
                'name' => $subjectName ?? match ($subjectCode) {
                    'AR' => 'اللغة العربية',
                    'MATH' => 'الرياضيات',
                    'EN' => 'اللغة الإنجليزية',
                    'PHY' => 'الفيزياء',
                    'CHEM' => 'الكيمياء',
                    'BIO' => 'الأحياء',
                    default => Str::title($subjectCode),
                },
                'ordering' => 10,
                'is_active' => true,
            ]
        );

        Unit::query()->updateOrCreate(
            [
                'subject_id' => $subject->id,
                'name' => 'الوحدة الأولى',
            ],
            [
                'ordering' => 1,
                'is_active' => true,
            ]
        );

        return $subject;
    }
}
