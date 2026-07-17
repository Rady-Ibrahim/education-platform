<?php

namespace App\Modules\Notifications\Services;

use App\Enums\SubscriptionStatus;
use App\Modules\Exams\Models\Exam;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Notifications\ExamPublishedNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentPendingReviewNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\SubscriptionExpiringNotification;

class NotificationService
{
    public function notifyPaymentConfirmed(Payment $payment): void
    {
        $payment->loadMissing('student');

        if ($payment->student) {
            $payment->student->notify(new PaymentConfirmedNotification($payment));
        }
    }

    public function notifyPaymentRejected(Payment $payment): void
    {
        $payment->loadMissing('student');

        if ($payment->student) {
            $payment->student->notify(new PaymentRejectedNotification($payment));
        }
    }

    public function notifyPaymentPendingReview(Payment $payment): void
    {
        $payment->loadMissing('teacher');

        if ($payment->teacher) {
            $payment->teacher->notify(new PaymentPendingReviewNotification($payment));
        }
    }

    public function notifyExamPublished(Exam $exam): void
    {
        $exam->loadMissing('creator.students');

        $students = $exam->creator?->students ?? collect();

        foreach ($students as $student) {
            $student->notify(new ExamPublishedNotification($exam));
        }
    }

    /**
     * إرسال تذكير للمشتركين الذين ينتهي اشتراكهم خلال الأيام المحددة.
     */
    public function notifyExpiringSubscriptions(int $withinDays = 3): int
    {
        $from = now()->startOfDay();
        $to = now()->addDays($withinDays)->endOfDay();

        $subscriptions = Subscription::query()
            ->with('student')
            ->where('status', SubscriptionStatus::Active)
            ->whereBetween('ends_at', [$from, $to])
            ->get();

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            if (! $subscription->student) {
                continue;
            }

            $subscription->student->notify(new SubscriptionExpiringNotification($subscription));
            $sent++;
        }

        return $sent;
    }
}
