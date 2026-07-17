<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    /**
     * @return array{
     *     student: User,
     *     subscriptions: Collection<int, Subscription>,
     *     payments: Collection<int, Payment>,
     *     invoices: Collection<int, Invoice>,
     *     confirmed_total: float,
     *     pending_total: float
     * }
     */
    public function statementForTeacher(User $teacher, User $student): array
    {
        $allowed = $teacher->hasRole(UserRole::Admin)
            || $teacher->students()->where('users.id', $student->id)->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'student' => 'الطالب خارج نطاقك.',
            ]);
        }

        $teacherId = $teacher->hasRole(UserRole::Admin) ? null : $teacher->id;

        $paymentsQuery = Payment::query()
            ->with(['subscription.plan', 'invoice'])
            ->where('student_id', $student->id)
            ->latest();

        $subscriptionsQuery = Subscription::query()
            ->with(['plan', 'subject'])
            ->where('student_id', $student->id)
            ->latest();

        if ($teacherId) {
            $paymentsQuery->where('teacher_id', $teacherId);
            $subscriptionsQuery->where('teacher_id', $teacherId);
        }

        $payments = $paymentsQuery->get();
        $subscriptions = $subscriptionsQuery->get();

        $invoices = Invoice::query()
            ->whereIn('payment_id', $payments->pluck('id'))
            ->latest()
            ->get();

        return [
            'student' => $student,
            'subscriptions' => $subscriptions,
            'payments' => $payments,
            'invoices' => $invoices,
            'confirmed_total' => (float) $payments->where('status', PaymentStatus::Confirmed)->sum('amount'),
            'pending_total' => (float) $payments->where('status', PaymentStatus::PendingReview)->sum('amount'),
        ];
    }

    public function latestPaymentForSubscription(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('subscription_id', $subscription->id)
            ->latest()
            ->first();
    }

    public function canSubmitProof(Subscription $subscription): bool
    {
        if ($subscription->status !== SubscriptionStatus::PendingPayment) {
            return false;
        }

        $latest = $this->latestPaymentForSubscription($subscription);

        return ! $latest || $latest->status === PaymentStatus::Rejected;
    }

    public function studentCanSubmitVodafone(Subscription $subscription): bool
    {
        if (! config('payments.student_vodafone_enabled')) {
            return false;
        }

        return $this->canSubmitProof($subscription);
    }
}
