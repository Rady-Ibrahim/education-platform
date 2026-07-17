<?php

namespace App\Livewire\Student;

use App\Enums\SubscriptionStatus;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageSubscriptions extends Component
{
    use WithFileUploads;

    public ?int $selectedPlanId = null;

    public ?int $payingSubscriptionId = null;

    public string $externalReference = '';

    public $proof;

    public function enroll(int $planId, EnrollmentService $enrollment): void
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($planId);
        $enrollment->enrollStudent(auth()->user(), $plan);
        session()->flash('status', 'تم إنشاء الاشتراك. أكمل الدفع لإرسال الإثبات.');
    }

    public function startPayment(int $subscriptionId): void
    {
        $subscription = Subscription::query()
            ->where('student_id', auth()->id())
            ->where('status', SubscriptionStatus::PendingPayment)
            ->findOrFail($subscriptionId);

        $this->payingSubscriptionId = $subscription->id;
        $this->externalReference = '';
        $this->proof = null;
    }

    public function submitProof(PaymentRecordService $payments): void
    {
        $this->validate([
            'externalReference' => ['required', 'string', 'min:4', 'max:100'],
            'proof' => ['nullable', 'image', 'max:4096'],
        ]);

        $subscription = Subscription::query()
            ->where('student_id', auth()->id())
            ->findOrFail($this->payingSubscriptionId);

        $payments->submitVodafoneProof(auth()->user(), $subscription, [
            'external_reference' => $this->externalReference,
        ], $this->proof);

        $this->payingSubscriptionId = null;
        $this->reset(['externalReference', 'proof']);
        session()->flash('status', 'تم إرسال إثبات الدفع وبانتظار مراجعة المدرس.');
    }

    public function render()
    {
        $student = auth()->user();
        $teacherIds = $student->teachers()->pluck('users.id');

        $plans = SubscriptionPlan::query()
            ->with(['subject.grade.stage', 'teacher'])
            ->where('is_active', true)
            ->whereIn('teacher_id', $teacherIds)
            ->latest()
            ->get();

        $subscriptions = Subscription::query()
            ->with(['subject', 'teacher', 'plan'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return view('livewire.student.manage-subscriptions', [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
        ]);
    }
}
