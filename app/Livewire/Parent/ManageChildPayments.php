<?php

namespace App\Livewire\Parent;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Modules\Payments\Services\StudentAccountService;
use App\Modules\Payments\Models\SubscriptionPlan;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageChildPayments extends Component
{
    use WithFileUploads;

    public int $studentId;

    public ?int $payingSubscriptionId = null;

    public string $externalReference = '';

    public $proof;

    public function mount(int $studentId, ParentLinkService $links): void
    {
        $student = User::query()->findOrFail($studentId);

        if (! $links->parentCanViewStudent(auth()->user(), $student)) {
            abort(403);
        }

        $this->studentId = $student->id;
    }

    public function enroll(int $planId, EnrollmentService $enrollment, ParentLinkService $links): void
    {
        $student = $this->student($links);
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($planId);
        $enrollment->enrollStudent($student, $plan);
        session()->flash('status', 'تم إنشاء الاشتراك. أكمل التحويل وأرسل الإثبات.');
    }

    public function startPayment(int $subscriptionId, ParentLinkService $links, StudentAccountService $accounts): void
    {
        $student = $this->student($links);
        $subscription = Subscription::query()
            ->where('student_id', $student->id)
            ->where('status', SubscriptionStatus::PendingPayment)
            ->findOrFail($subscriptionId);

        if (! $accounts->canSubmitProof($subscription)) {
            session()->flash('status', 'يوجد إثبات قيد المراجعة بالفعل.');

            return;
        }

        $this->payingSubscriptionId = $subscription->id;
        $this->externalReference = '';
        $this->proof = null;
    }

    public function submitProof(PaymentRecordService $payments, ParentLinkService $links): void
    {
        $this->validate([
            'externalReference' => ['required', 'string', 'min:4', 'max:100'],
            'proof' => ['nullable', 'image', 'max:4096'],
        ]);

        $student = $this->student($links);
        $subscription = Subscription::query()
            ->where('student_id', $student->id)
            ->findOrFail($this->payingSubscriptionId);

        $payments->submitVodafoneProofForChild(auth()->user(), $student, $subscription, [
            'external_reference' => $this->externalReference,
        ], $this->proof);

        $this->payingSubscriptionId = null;
        $this->reset(['externalReference', 'proof']);
        session()->flash('status', 'تم إرسال إثبات فودافون كاش وبانتظار مراجعة المدرس.');
    }

    public function render(ParentLinkService $links, PaymentRecordService $payments, StudentAccountService $accounts)
    {
        $student = $this->student($links);
        $teacherIds = $student->teachers()->pluck('users.id');

        $plans = SubscriptionPlan::query()
            ->with(['subject.grade.stage', 'teacher'])
            ->where('is_active', true)
            ->whereIn('teacher_id', $teacherIds)
            ->latest()
            ->get();

        $subscriptions = Subscription::query()
            ->with(['subject', 'teacher', 'plan', 'payments' => fn ($q) => $q->latest()])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        $instructions = [];
        foreach ($subscriptions->where('status', SubscriptionStatus::PendingPayment) as $subscription) {
            $instructions[$subscription->id] = $payments->paymentInstructionsForSubscription($subscription);
        }

        $canSubmit = [];
        foreach ($subscriptions as $subscription) {
            $canSubmit[$subscription->id] = $accounts->canSubmitProof($subscription);
        }

        return view('livewire.parent.manage-child-payments', [
            'student' => $student,
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'instructions' => $instructions,
            'canSubmit' => $canSubmit,
        ]);
    }

    private function student(ParentLinkService $links): User
    {
        $student = User::query()->findOrFail($this->studentId);
        abort_unless($links->parentCanViewStudent(auth()->user(), $student), 403);

        return $student;
    }
}
