<?php

namespace App\Livewire\Teacher;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\StudentAccountService;
use Livewire\Component;

class StudentAccountStatement extends Component
{
    public int $studentId;

    public function mount(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    public function suspendSubscription(int $subscriptionId, EnrollmentService $enrollment): void
    {
        $subscription = Subscription::query()->findOrFail($subscriptionId);
        $enrollment->suspend(auth()->user(), $subscription);
        session()->flash('status', 'تم إيقاف الاشتراك.');
    }

    public function reactivateSubscription(int $subscriptionId, EnrollmentService $enrollment): void
    {
        $subscription = Subscription::query()->findOrFail($subscriptionId);
        $enrollment->reactivate(auth()->user(), $subscription);
        session()->flash('status', 'تم إعادة تفعيل الاشتراك.');
    }

    public function render(StudentAccountService $accounts)
    {
        $student = User::query()->findOrFail($this->studentId);
        $statement = $accounts->statementForTeacher(auth()->user(), $student);

        return view('livewire.teacher.student-account-statement', array_merge($statement, [
            'activeStatus' => SubscriptionStatus::Active,
            'suspendedStatus' => SubscriptionStatus::Suspended,
        ]));
    }
}
