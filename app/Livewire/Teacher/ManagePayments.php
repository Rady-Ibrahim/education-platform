<?php

namespace App\Livewire\Teacher;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Modules\Payments\Services\PaymentReviewService;
use App\Modules\Payments\Services\SubscriptionPlanService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManagePayments extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'cash';

    public string $cashSearch = '';

    public ?int $enrollStudentId = null;

    public ?int $enrollPlanId = null;

    public string $cashNotes = '';

    public ?int $rejectingPaymentId = null;

    public string $rejectionReason = '';

    public string $newPlanName = 'اشتراك شهري';

    public ?int $newPlanSubjectId = null;

    public string $newPlanPrice = '';

    public int $newPlanDays = 30;

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['cash', 'vodafone', 'plans', 'settings'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
        $this->rejectingPaymentId = null;
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
        session()->flash('status', 'اتسجّل على الخطة — هيظهر في دفتر التحصيل لحد ما تستلم الكاش.');
        $this->tab = 'cash';
    }

    public function collectCash(int $subscriptionId, PaymentRecordService $payments): void
    {
        $subscription = Subscription::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('status', SubscriptionStatus::PendingPayment)
            ->findOrFail($subscriptionId);

        $payments->recordCash(auth()->user(), $subscription->student, $subscription, [
            'notes' => $this->cashNotes !== '' ? $this->cashNotes : 'تحصيل كاش — نهاية الشهر',
        ]);

        $this->cashNotes = '';
        session()->flash('status', 'تم استلام كاش '.$subscription->student->name.' وتفعيل الاشتراك.');
    }

    public function confirm(int $paymentId, PaymentReviewService $review): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $review->confirm(auth()->user(), $payment);
        session()->flash('status', 'تم تأكيد فودافون كاش وتفعيل الاشتراك.');
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

    public function render()
    {
        $teacher = auth()->user();

        $pendingCash = Subscription::query()
            ->with(['student', 'plan', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::PendingPayment)
            ->when($this->cashSearch !== '', function ($q) {
                $term = '%'.$this->cashSearch.'%';
                $q->whereHas('student', function ($student) use ($term) {
                    $student->where('name', 'like', $term)
                        ->orWhere('student_code', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->get();

        $pendingVodafone = Payment::query()
            ->with(['student', 'subscription.plan'])
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

        $cashDueTotal = (float) $pendingCash->sum(fn (Subscription $s) => (float) ($s->plan?->price ?? 0));

        return view('livewire.teacher.manage-payments', [
            'pendingCash' => $pendingCash,
            'pendingVodafone' => $pendingVodafone,
            'students' => $students,
            'plans' => $plans,
            'subjects' => $subjects,
            'confirmedTotal' => $confirmedTotal,
            'cashDueTotal' => $cashDueTotal,
            'pendingVodafoneCount' => Payment::query()
                ->where('teacher_id', $teacher->id)
                ->where('status', PaymentStatus::PendingReview)
                ->where('channel', PaymentChannel::VodafoneCash)
                ->count(),
        ]);
    }
}
