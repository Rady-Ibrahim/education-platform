<?php

namespace App\Modules\Payments\Services;

use App\Enums\PlatformSubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\PlatformBillingSetting;
use App\Modules\Payments\Models\PlatformSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformBillingService
{
    public function settings(): PlatformBillingSetting
    {
        return PlatformBillingSetting::current();
    }

    public function ensureSubscription(User $teacher): PlatformSubscription
    {
        if (! $teacher->hasRole(UserRole::Teacher)) {
            throw ValidationException::withMessages([
                'teacher' => 'الحساب ليس مدرسًا.',
            ]);
        }

        $existing = PlatformSubscription::query()->where('teacher_id', $teacher->id)->first();
        if ($existing) {
            return $this->refreshStatus($existing);
        }

        $settings = $this->settings();
        $anchor = $teacher->approved_at ?? $teacher->created_at ?? now();

        return PlatformSubscription::query()->create([
            'teacher_id' => $teacher->id,
            'status' => PlatformSubscriptionStatus::Trialing,
            'trial_ends_at' => $anchor->copy()->addDays($settings->trial_days),
            'current_period_ends_at' => null,
            'amount' => $settings->monthly_fee,
        ]);
    }

    public function refreshStatus(PlatformSubscription $subscription): PlatformSubscription
    {
        if ($subscription->status === PlatformSubscriptionStatus::Trialing
            && $subscription->trial_ends_at
            && $subscription->trial_ends_at->isPast()) {
            $subscription->update([
                'status' => PlatformSubscriptionStatus::PastDue,
            ]);
        }

        if ($subscription->status === PlatformSubscriptionStatus::Active
            && $subscription->current_period_ends_at
            && $subscription->current_period_ends_at->isPast()) {
            $subscription->update([
                'status' => PlatformSubscriptionStatus::PastDue,
            ]);
        }

        return $subscription->refresh();
    }

    public function teacherHasAccess(User $teacher): bool
    {
        if (! $teacher->hasRole(UserRole::Teacher)) {
            return true;
        }

        $subscription = $this->ensureSubscription($teacher);

        return $subscription->allowsAccess();
    }

    public function markPendingPayment(PlatformSubscription $subscription): PlatformSubscription
    {
        $subscription->update([
            'status' => PlatformSubscriptionStatus::PendingPayment,
        ]);

        return $subscription->refresh();
    }

    public function activateAfterPayment(PlatformSubscription $subscription): PlatformSubscription
    {
        $settings = $this->settings();
        $base = now();
        if ($subscription->current_period_ends_at && $subscription->current_period_ends_at->isFuture()) {
            $base = $subscription->current_period_ends_at->copy();
        }

        $subscription->update([
            'status' => PlatformSubscriptionStatus::Active,
            'current_period_ends_at' => $base->addDays($settings->period_days),
            'amount' => $settings->monthly_fee,
        ]);

        return $subscription->refresh();
    }

    /**
     * @param  array{vodafone_cash_number?: string|null, payment_instructions?: string|null, trial_days?: int, monthly_fee?: float|int, period_days?: int}  $data
     */
    public function updateSettings(User $admin, array $data): PlatformBillingSetting
    {
        if (! $admin->hasRole(UserRole::Admin)) {
            throw ValidationException::withMessages([
                'admin' => 'غير مصرح.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $settings = $this->settings();
            $settings->update([
                'vodafone_cash_number' => array_key_exists('vodafone_cash_number', $data)
                    ? (($data['vodafone_cash_number'] ?? null) ?: null)
                    : $settings->vodafone_cash_number,
                'payment_instructions' => array_key_exists('payment_instructions', $data)
                    ? (($data['payment_instructions'] ?? null) ?: null)
                    : $settings->payment_instructions,
                'trial_days' => $data['trial_days'] ?? $settings->trial_days,
                'monthly_fee' => $data['monthly_fee'] ?? $settings->monthly_fee,
                'period_days' => $data['period_days'] ?? $settings->period_days,
            ]);

            return $settings->refresh();
        });
    }
}
