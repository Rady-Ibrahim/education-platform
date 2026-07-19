<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PlatformSubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\PlatformPayment;
use App\Modules\Payments\Models\PlatformSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformPaymentService
{
    public function __construct(
        private readonly PlatformBillingService $billing,
    ) {}

    /**
     * @param  array{external_reference: string, notes?: string|null}  $data
     */
    public function submitVodafoneProof(User $teacher, array $data, ?UploadedFile $proof = null): PlatformPayment
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'غير مصرح.',
            ]);
        }

        $subscription = $this->billing->ensureSubscription($teacher);
        $settings = $this->billing->settings();

        if (! filled($settings->vodafone_cash_number)) {
            throw ValidationException::withMessages([
                'payment' => 'الإدارة لم تضبط رقم فودافون كاش للمنصة بعد.',
            ]);
        }

        $pending = PlatformPayment::query()
            ->where('platform_subscription_id', $subscription->id)
            ->where('status', PaymentStatus::PendingReview)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'payment' => 'لديك إثبات قيد المراجعة بالفعل.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $subscription, $settings, $data, $proof) {
            if (! $proof) {
                throw ValidationException::withMessages([
                    'proof' => 'صورة وصل فودافون كاش مطلوبة.',
                ]);
            }

            $path = $proof->store('platform-payment-proofs', 'public');

            $payment = PlatformPayment::query()->create([
                'teacher_id' => $teacher->id,
                'platform_subscription_id' => $subscription->id,
                'channel' => PaymentChannel::VodafoneCash,
                'provider' => 'manual',
                'amount' => $subscription->amount ?? $settings->monthly_fee,
                'external_reference' => $data['external_reference'],
                'proof_path' => $path,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $teacher->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->billing->markPendingPayment($subscription);

            return $payment;
        });
    }

    public function confirm(User $admin, PlatformPayment $payment): PlatformPayment
    {
        if (! $admin->hasRole(UserRole::Admin)) {
            throw ValidationException::withMessages([
                'admin' => 'غير مصرح.',
            ]);
        }

        if (! $payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => 'الدفعة ليست بانتظار المراجعة.',
            ]);
        }

        return DB::transaction(function () use ($admin, $payment) {
            $payment->update([
                'status' => PaymentStatus::Confirmed,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $subscription = PlatformSubscription::query()->findOrFail($payment->platform_subscription_id);
            $this->billing->activateAfterPayment($subscription);

            return $payment->refresh();
        });
    }

    public function reject(User $admin, PlatformPayment $payment, string $reason): PlatformPayment
    {
        if (! $admin->hasRole(UserRole::Admin)) {
            throw ValidationException::withMessages([
                'admin' => 'غير مصرح.',
            ]);
        }

        if (! $payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => 'الدفعة ليست بانتظار المراجعة.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'سبب الرفض مطلوب.',
            ]);
        }

        return DB::transaction(function () use ($admin, $payment, $reason) {
            $payment->update([
                'status' => PaymentStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $subscription = PlatformSubscription::query()->findOrFail($payment->platform_subscription_id);
            $subscription->update([
                'status' => PlatformSubscriptionStatus::PastDue,
            ]);

            return $payment->refresh();
        });
    }
}
