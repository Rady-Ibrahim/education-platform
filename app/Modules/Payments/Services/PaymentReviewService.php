<?php

namespace App\Modules\Payments\Services;

use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\SubscriptionCharge;
use App\Modules\Payments\Models\StudentFee;
use App\Modules\Notifications\Services\NotificationService;
use App\Support\AuditLogger;
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

            $subscription = $payment->subscription_id ? $payment->subscription : null;
            $wasActive = $subscription?->isActive() ?? false;

            if ($payment->subscription_charge_id) {
                $this->refreshLinkedCharge($payment->subscription_charge_id);
            }

            if ($payment->student_fee_id) {
                $fee = StudentFee::query()->find($payment->student_fee_id);
                if ($fee) {
                    app(StudentFeeService::class)->refreshFeeStatus($fee);
                }
            }

            if ($subscription && ! $wasActive) {
                $this->enrollment->activate($subscription);
            } elseif ($subscription && $wasActive && $payment->subscription_charge_id) {
                $charge = SubscriptionCharge::query()->find($payment->subscription_charge_id);
                if ($charge?->fresh()->status === ChargeStatus::Paid) {
                    $this->extendSubscriptionForRenewal($subscription);
                }
            }

            if ($payment->subscription_id || $payment->student_fee_id) {
                $this->invoices->issueForPayment($payment->fresh());
            }

            return $payment->fresh();
        });

        $this->notifications->notifyPaymentConfirmed($confirmed);
        AuditLogger::payment('confirmed', [
            'payment_id' => $confirmed->id,
            'student_id' => $confirmed->student_id,
            'reviewer_id' => $reviewer->id,
            'amount' => (float) $confirmed->amount,
        ]);

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
        AuditLogger::payment('rejected', [
            'payment_id' => $rejected->id,
            'student_id' => $rejected->student_id,
            'reviewer_id' => $reviewer->id,
            'reason' => $reason,
        ]);

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

    private function refreshLinkedCharge(int $chargeId): void
    {
        $charge = SubscriptionCharge::query()->find($chargeId);
        if (! $charge || $charge->status === ChargeStatus::Waived) {
            return;
        }

        $paid = (float) $charge->payments()
            ->where('status', PaymentStatus::Confirmed)
            ->sum('amount');
        $net = max(0, (float) $charge->expected_amount - (float) $charge->discount_amount);

        $status = match (true) {
            $paid <= 0 => ChargeStatus::Due,
            $paid + 0.001 >= $net => ChargeStatus::Paid,
            default => ChargeStatus::Partial,
        };

        if ($charge->status !== $status) {
            $charge->update(['status' => $status]);
        }
    }

    private function extendSubscriptionForRenewal(\App\Modules\Payments\Models\Subscription $subscription): void
    {
        $subscription->loadMissing('plan');
        $days = (int) ($subscription->plan?->duration_days ?: 30);
        $base = $subscription->ends_at && $subscription->ends_at->isFuture()
            ? $subscription->ends_at->copy()
            : now();

        $subscription->update([
            'ends_at' => $base->addDays($days),
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
