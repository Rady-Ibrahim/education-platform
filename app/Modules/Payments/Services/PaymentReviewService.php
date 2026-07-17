<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentReviewService
{
    public function __construct(
        private readonly EnrollmentService $enrollment,
        private readonly InvoiceService $invoices,
        private readonly NotificationService $notifications,
    ) {}

    public function confirm(User $reviewer, Payment $payment): Payment
    {
        $this->assertCanReview($reviewer, $payment);

        if ($payment->status !== PaymentStatus::PendingReview) {
            throw ValidationException::withMessages([
                'payment' => 'الدفعة ليست بانتظار المراجعة.',
            ]);
        }

        $confirmed = DB::transaction(function () use ($reviewer, $payment) {
            $payment->update([
                'status' => PaymentStatus::Confirmed,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            if ($payment->subscription_id) {
                $subscription = $payment->subscription;
                if ($subscription && ! $subscription->isActive()) {
                    $this->enrollment->activate($subscription);
                }
                $this->invoices->issueForPayment($payment->fresh());
            }

            return $payment->fresh();
        });

        $this->notifications->notifyPaymentConfirmed($confirmed);

        return $confirmed;
    }

    public function reject(User $reviewer, Payment $payment, string $reason): Payment
    {
        $this->assertCanReview($reviewer, $payment);

        if ($payment->status !== PaymentStatus::PendingReview) {
            throw ValidationException::withMessages([
                'payment' => 'الدفعة ليست بانتظار المراجعة.',
            ]);
        }

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $rejected = $payment->fresh();
        $this->notifications->notifyPaymentRejected($rejected);

        return $rejected;
    }

    private function assertCanReview(User $reviewer, Payment $payment): void
    {
        if ($reviewer->hasRole(UserRole::Admin)) {
            return;
        }

        if ($reviewer->hasRole(UserRole::Teacher)) {
            if ($payment->teacher_id !== $reviewer->id) {
                throw ValidationException::withMessages([
                    'payment' => 'الدفعة خارج نطاقك.',
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'payment' => 'غير مصرح بمراجعة الدفع.',
        ]);
    }
}
