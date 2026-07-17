<?php

namespace App\Livewire\Teacher;

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
use Livewire\Component;
use Livewire\WithPagination;

class ManagePayments extends Component
{
    use WithPagination;

    public ?int $enrollStudentId = null;

    public ?int $enrollPlanId = null;

    public ?int $cashStudentId = null;

    public ?int $cashSubscriptionId = null;

    public string $cashNotes = '';

    public ?int $rejectingPaymentId = null;

    public string $rejectionReason = '';

    public string $newPlanName = '';

    public ?int $newPlanSubjectId = null;

    public string $newPlanPrice = '';

    public function createPlan(SubscriptionPlanService $plans): void
    {
        $this->validate([
            'newPlanName' => ['required', 'string', 'max:120'],
            'newPlanSubjectId' => ['required', 'integer', 'exists:subjects,id'],
            'newPlanPrice' => ['required', 'numeric', 'min:1'],
        ]);

        $subject = \App\Modules\Academic\Models\Subject::query()->findOrFail($this->newPlanSubjectId);
        $plans->create(auth()->user(), $subject, [
            'name' => $this->newPlanName,
            'price' => (float) $this->newPlanPrice,
        ]);

        $this->reset(['newPlanName', 'newPlanSubjectId', 'newPlanPrice']);
        session()->flash('status', 'تم إنشاء خطة الاشتراك.');
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
        session()->flash('status', 'تم تسجيل الطالب على الخطة.');
    }

    public function recordCash(PaymentRecordService $payments): void
    {
        $this->validate([
            'cashStudentId' => ['required', 'integer'],
            'cashSubscriptionId' => ['required', 'integer', 'exists:subscriptions,id'],
        ]);

        $student = User::query()->findOrFail($this->cashStudentId);
        $subscription = Subscription::query()->findOrFail($this->cashSubscriptionId);

        $payments->recordCash(auth()->user(), $student, $subscription, [
            'notes' => $this->cashNotes ?: null,
        ]);

        $this->reset(['cashStudentId', 'cashSubscriptionId', 'cashNotes']);
        session()->flash('status', 'تم تسجيل الدفع النقدي وتفعيل الاشتراك.');
    }

    public function confirm(int $paymentId, PaymentReviewService $review): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $review->confirm(auth()->user(), $payment);
        session()->flash('status', 'تم تأكيد الدفع وتفعيل الاشتراك.');
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

        $pendingPayments = Payment::query()
            ->with(['student', 'subscription.plan'])
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::PendingReview)
            ->latest()
            ->paginate(10);

        $students = $teacher->students()->orderBy('name')->get();

        $plans = SubscriptionPlan::query()
            ->with('subject.grade.stage')
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->get();

        $pendingSubscriptions = Subscription::query()
            ->with(['student', 'plan', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::PendingPayment)
            ->when($this->cashStudentId, fn ($q) => $q->where('student_id', $this->cashStudentId))
            ->get();

        $subjects = app(\App\Modules\Academic\Services\AcademicStructureService::class)
            ->subjectsForTeacher($teacher);

        $confirmedTotal = (float) Payment::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::Confirmed)
            ->sum('amount');

        return view('livewire.teacher.manage-payments', [
            'pendingPayments' => $pendingPayments,
            'students' => $students,
            'plans' => $plans,
            'pendingSubscriptions' => $pendingSubscriptions,
            'subjects' => $subjects,
            'confirmedTotal' => $confirmedTotal,
        ]);
    }
}
