<?php

namespace App\Livewire\Teacher;

use App\Enums\FeeCategory;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\StudentFee;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionCharge;
use App\Modules\Payments\Models\SubscriptionPlan;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\MonthlyCollectionService;
use App\Modules\Payments\Services\PaymentReviewService;
use App\Modules\Payments\Services\StudentFeeService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManagePayments extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'cash';

    public string $billingMonth = '';

    public bool $owingOnly = true;

    public string $cashSearch = '';

    public ?int $collectChargeId = null;

    public string $collectAmount = '';

    public string $collectDiscount = '0';

    public string $cashNotes = '';

    public ?int $enrollStudentId = null;

    public ?int $enrollPlanId = null;

    public ?int $rejectingPaymentId = null;

    public string $rejectionReason = '';

    public string $newPlanName = 'اشتراك شهري';

    public ?int $newPlanSubjectId = null;

    public string $newPlanPrice = '';

    public int $newPlanDays = 30;

    public string $feeTitle = 'كتب';

    public string $feeCategory = 'books';

    public string $feeAmount = '';

    public ?int $feeStudentId = null;

    public ?int $feeSubjectId = null;

    public string $feeNotes = '';

    public string $feeSearch = '';

    public bool $feeOpenOnly = true;

    public ?int $collectFeeId = null;

    public string $collectFeeAmount = '';

    public string $collectFeeDiscount = '0';

    public string $collectFeeNotes = '';

    public function mount(): void
    {
        $this->billingMonth = now()->format('Y-m');
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['cash', 'fees', 'vodafone', 'plans', 'settings'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
        $this->rejectingPaymentId = null;
        $this->collectChargeId = null;
        $this->collectFeeId = null;
    }

    public function generateMonth(MonthlyCollectionService $collection): void
    {
        $count = $collection->generateMonthDues(auth()->user(), $this->billingMonth.'-01')->count();
        session()->flash('status', 'تم تجهيز مستحقات الشهر لـ '.$count.' اشتراك.');
    }

    public function startCollect(int $chargeId, MonthlyCollectionService $collection): void
    {
        $charge = SubscriptionCharge::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($chargeId);

        $collection->refreshChargeStatus($charge);
        $charge = $charge->fresh();

        $this->collectChargeId = $charge->id;
        $this->collectAmount = (string) $charge->remainingAmount();
        $this->collectDiscount = (string) $charge->discount_amount;
        $this->cashNotes = 'تحصيل '.$charge->monthLabel();
    }

    public function cancelCollect(): void
    {
        $this->collectChargeId = null;
        $this->collectAmount = '';
        $this->collectDiscount = '0';
        $this->cashNotes = '';
    }

    public function collectCharge(MonthlyCollectionService $collection): void
    {
        $validated = $this->validate([
            'collectChargeId' => ['required', 'exists:subscription_charges,id'],
            'collectAmount' => ['required', 'numeric', 'min:0.5'],
            'collectDiscount' => ['nullable', 'numeric', 'min:0'],
            'cashNotes' => ['nullable', 'string', 'max:255'],
        ]);

        $charge = SubscriptionCharge::query()->findOrFail($validated['collectChargeId']);

        $payment = $collection->collectCash(auth()->user(), $charge, [
            'amount' => (float) $validated['collectAmount'],
            'discount' => $validated['collectDiscount'] !== '' ? (float) $validated['collectDiscount'] : null,
            'notes' => $validated['cashNotes'] ?: null,
        ]);

        $this->cancelCollect();
        session()->flash(
            'status',
            'تم التحصيل — إيصال '.$payment->receipt_number.' بمبلغ '.number_format((float) $payment->amount, 0).' ج.م'
        );
    }

    public function collectFull(int $chargeId, MonthlyCollectionService $collection): void
    {
        $charge = SubscriptionCharge::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($chargeId);

        $payment = $collection->collectCash(auth()->user(), $charge, [
            'notes' => $this->cashNotes !== '' ? $this->cashNotes : null,
        ]);

        session()->flash(
            'status',
            'تم تحصيل كامل — إيصال '.$payment->receipt_number
        );
    }

    public function createPlan(SubscriptionPlanService $plans): void
    {
        $this->validate([
            'newPlanName' => ['required', 'string', 'max:120'],
            'newPlanSubjectId' => ['required', 'integer', 'exists:subjects,id'],
            'newPlanPrice' => ['required', 'numeric', 'min:1'],
            'newPlanDays' => ['required', 'integer', 'min:1', 'max:366'],
        ]);

        $subject = \App\Modules\Academic\Models\Subject::query()->findOrFail($this->newPlanSubjectId);
        $plans->create(auth()->user(), $subject, [
            'name' => $this->newPlanName,
            'price' => (float) $this->newPlanPrice,
            'duration_days' => $this->newPlanDays,
            'description' => 'اشتراك شهري — التحصيل عادة نهاية الشهر كاش في السنتر أو فودافون من ولي الأمر.',
        ]);

        $this->reset(['newPlanSubjectId', 'newPlanPrice']);
        $this->newPlanName = 'اشتراك شهري';
        $this->newPlanDays = 30;
        session()->flash('status', 'تم إنشاء الخطة الشهرية.');
        $this->tab = 'plans';
    }

    public function enrollStudent(EnrollmentService $enrollment): void
    {
        $this->validate([
            'enrollStudentId' => ['required', 'integer'],
            'enrollPlanId' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $student = User::query()->findOrFail($this->enrollStudentId);
        $plan = SubscriptionPlan::query()->findOrFail($this->enrollPlanId);
        $enrollment->enrollStudentByTeacher(auth()->user(), $student, $plan);

        $this->reset(['enrollStudentId', 'enrollPlanId']);
        session()->flash('status', 'اتسجّل على الخطة — هيظهر في دفتر الشهر الحالي.');
        $this->tab = 'cash';
    }

    public function confirm(int $paymentId, PaymentReviewService $review): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $review->confirm(auth()->user(), $payment);

        $label = $payment->student_fee_id ? 'المصروف' : 'الاشتراك';
        session()->flash('status', 'تم تأكيد فودافون كاش وتسوية '.$label.'.');
    }

    public function startReject(int $paymentId): void
    {
        $this->rejectingPaymentId = $paymentId;
        $this->rejectionReason = '';
    }

    public function confirmReject(PaymentReviewService $review): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $payment = Payment::query()->findOrFail($this->rejectingPaymentId);
        $review->reject(auth()->user(), $payment, $this->rejectionReason);

        $this->rejectingPaymentId = null;
        $this->rejectionReason = '';
        session()->flash('status', 'تم رفض الدفع.');
    }

    public function createFee(StudentFeeService $fees): void
    {
        $validated = $this->validate([
            'feeTitle' => ['required', 'string', 'max:160'],
            'feeCategory' => ['required', 'in:books,materials,transport,other'],
            'feeAmount' => ['required', 'numeric', 'min:1'],
            'feeStudentId' => ['required', 'integer'],
            'feeSubjectId' => ['nullable', 'integer', 'exists:subjects,id'],
            'feeNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $student = User::query()->findOrFail($validated['feeStudentId']);
        $fees->create(auth()->user(), $student, [
            'title' => $validated['feeTitle'],
            'category' => $validated['feeCategory'],
            'expected_amount' => (float) $validated['feeAmount'],
            'subject_id' => $validated['feeSubjectId'] ?: null,
            'notes' => $validated['feeNotes'] ?: null,
        ]);

        $this->reset(['feeAmount', 'feeStudentId', 'feeSubjectId', 'feeNotes']);
        $this->feeTitle = 'كتب';
        $this->feeCategory = 'books';
        session()->flash('status', 'تم تسجيل المصروف — يظهر لولي الأمر لدفع فودافون أو حصّله كاش من الطالب.');
        $this->tab = 'fees';
    }

    public function startCollectFee(int $feeId, StudentFeeService $fees): void
    {
        $fee = StudentFee::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($feeId);

        $fees->refreshFeeStatus($fee);
        $fee = $fee->fresh();

        $this->collectFeeId = $fee->id;
        $this->collectFeeAmount = (string) $fee->remainingAmount();
        $this->collectFeeDiscount = (string) $fee->discount_amount;
        $this->collectFeeNotes = 'تحصيل كاش — '.$fee->title;
    }

    public function cancelCollectFee(): void
    {
        $this->collectFeeId = null;
        $this->collectFeeAmount = '';
        $this->collectFeeDiscount = '0';
        $this->collectFeeNotes = '';
    }

    public function collectFee(StudentFeeService $fees): void
    {
        $validated = $this->validate([
            'collectFeeId' => ['required', 'exists:student_fees,id'],
            'collectFeeAmount' => ['required', 'numeric', 'min:0.5'],
            'collectFeeDiscount' => ['nullable', 'numeric', 'min:0'],
            'collectFeeNotes' => ['nullable', 'string', 'max:255'],
        ]);

        $fee = StudentFee::query()->findOrFail($validated['collectFeeId']);
        $payment = $fees->collectCash(auth()->user(), $fee, [
            'amount' => (float) $validated['collectFeeAmount'],
            'discount' => $validated['collectFeeDiscount'] !== '' ? (float) $validated['collectFeeDiscount'] : null,
            'notes' => $validated['collectFeeNotes'] ?: null,
        ]);

        $this->cancelCollectFee();
        session()->flash(
            'status',
            'تم تحصيل المصروف — إيصال '.$payment->receipt_number.' بمبلغ '.number_format((float) $payment->amount, 0).' ج.م'
        );
    }

    public function collectFeeFull(int $feeId, StudentFeeService $fees): void
    {
        $fee = StudentFee::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($feeId);

        $payment = $fees->collectCash(auth()->user(), $fee, [
            'notes' => $this->collectFeeNotes !== '' ? $this->collectFeeNotes : null,
        ]);

        session()->flash('status', 'تم تحصيل المصروف كاملًا — إيصال '.$payment->receipt_number);
    }

    public function waiveFee(int $feeId, StudentFeeService $fees): void
    {
        $fee = StudentFee::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($feeId);

        $fees->waive(auth()->user(), $fee);
        session()->flash('status', 'تم إعفاء المصروف.');
    }

    public function render(MonthlyCollectionService $collection, StudentFeeService $fees)
    {
        $teacher = auth()->user();
        $monthKey = $this->billingMonth !== '' ? $this->billingMonth.'-01' : now()->toDateString();

        $charges = $collection->listForTeacher(
            $teacher,
            $monthKey,
            $this->owingOnly,
            $this->cashSearch !== '' ? $this->cashSearch : null,
        );

        $owingTotal = $collection->owingTotalForMonth($teacher, $monthKey);
        $owingCount = $collection->owingCountForMonth($teacher, $monthKey);

        $studentFees = $fees->listForTeacher(
            $teacher,
            $this->feeOpenOnly,
            $this->feeSearch !== '' ? $this->feeSearch : null,
        );
        $openFeesTotal = $fees->openFeesTotalForTeacher($teacher);
        $openFeesCount = $fees->openFeesCountForTeacher($teacher);

        $pendingVodafone = Payment::query()
            ->with(['student', 'subscription.plan', 'studentFee'])
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::PendingReview)
            ->where('channel', PaymentChannel::VodafoneCash)
            ->latest()
            ->paginate(10, pageName: 'vodafonePage');

        $students = $teacher->students()->orderBy('name')->get();

        $plans = SubscriptionPlan::query()
            ->with('subject.grade.stage')
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->get();

        $subjects = app(\App\Modules\Academic\Services\AcademicStructureService::class)
            ->subjectsForTeacher($teacher);

        $confirmedTotal = (float) Payment::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::Confirmed)
            ->sum('amount');

        $collectCharge = $this->collectChargeId
            ? SubscriptionCharge::query()->with('student')->find($this->collectChargeId)
            : null;

        $collectFee = $this->collectFeeId
            ? StudentFee::query()->with('student')->find($this->collectFeeId)
            : null;

        return view('livewire.teacher.manage-payments', [
            'charges' => $charges,
            'collectCharge' => $collectCharge,
            'studentFees' => $studentFees,
            'collectFee' => $collectFee,
            'feeCategories' => FeeCategory::cases(),
            'openFeesTotal' => $openFeesTotal,
            'openFeesCount' => $openFeesCount,
            'pendingVodafone' => $pendingVodafone,
            'students' => $students,
            'plans' => $plans,
            'subjects' => $subjects,
            'confirmedTotal' => $confirmedTotal,
            'owingTotal' => $owingTotal,
            'owingCount' => $owingCount,
            'pendingVodafoneCount' => Payment::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', PaymentStatus::PendingReview)
                ->where('channel', PaymentChannel::VodafoneCash)
                ->count(),
            'pendingCashCount' => Subscription::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', SubscriptionStatus::PendingPayment)
                ->count(),
        ]);
    }
}
