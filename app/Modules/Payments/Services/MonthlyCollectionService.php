<?php

namespace App\Modules\Payments\Services;

use App\Enums\ChargeStatus;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionCharge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyCollectionService
{
    public function __construct(
        private readonly PaymentReviewService $review,
    ) {}

    public function monthStart(Carbon|string|null $month = null): Carbon
    {
        return Carbon::parse($month ?? now())->startOfMonth()->startOfDay();
    }

    public function ensureChargeForSubscription(Subscription $subscription, Carbon|string|null $month = null): SubscriptionCharge
    {
        $subscription->loadMissing('plan');
        $billingMonth = $this->monthStart($month);

        $expected = (float) ($subscription->plan?->price ?? 0);
        if ($expected <= 0) {
            throw ValidationException::withMessages([
                'plan' => 'خطة الاشتراك بدون مبلغ شهري.',
            ]);
        }

        $existing = SubscriptionCharge::query()
            ->where('subscription_id', $subscription->id)
            ->whereDate('billing_month', $billingMonth->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        return SubscriptionCharge::query()->create([
            'subscription_id' => $subscription->id,
            'billing_month' => $billingMonth->toDateString(),
            'student_id' => $subscription->student_id,
            'teacher_id' => $subscription->teacher_id,
            'expected_amount' => $expected,
            'discount_amount' => 0,
            'status' => ChargeStatus::Due,
        ]);
    }

    /**
     * يولّد مستحقات الشهر لكل اشتراكات المدرس (معلقة أو نشطة).
     *
     * @return Collection<int, SubscriptionCharge>
     */
    public function generateMonthDues(User $teacher, Carbon|string|null $month = null): Collection
    {
        $this->assertTeacher($teacher);
        $billingMonth = $this->monthStart($month);

        $subscriptions = Subscription::query()
            ->with('plan')
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', [
                SubscriptionStatus::PendingPayment->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Suspended->value,
            ])
            ->get();

        $created = collect();

        foreach ($subscriptions as $subscription) {
            if (! $subscription->plan || (float) $subscription->plan->price <= 0) {
                continue;
            }

            $charge = $this->ensureChargeForSubscription($subscription, $billingMonth);
            $this->refreshChargeStatus($charge);
            $created->push($charge->fresh());
        }

        return $created;
    }

    /**
     * @return Collection<int, SubscriptionCharge>
     */
    public function listForTeacher(
        User $teacher,
        Carbon|string|null $month = null,
        bool $owingOnly = true,
        ?string $search = null,
    ): Collection {
        $this->assertTeacher($teacher);
        $billingMonth = $this->monthStart($month);

        $query = SubscriptionCharge::query()
            ->with(['student', 'subscription.plan', 'subscription.subject', 'payments' => fn ($q) => $q->where('status', PaymentStatus::Confirmed)])
            ->where('teacher_id', $teacher->id)
            ->whereDate('billing_month', $billingMonth->toDateString())
            ->when($search, function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->whereHas('student', function ($student) use ($term) {
                    $student->where('name', 'like', $term)
                        ->orWhere('student_code', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy('status')
            ->orderBy('id');

        $charges = $query->get()->each(fn (SubscriptionCharge $c) => $this->refreshChargeStatus($c));

        if ($owingOnly) {
            return $charges->filter(fn (SubscriptionCharge $c) => $c->fresh()->status->isOpen())->values();
        }

        return $charges->values();
    }

    /**
     * @param  array{amount?: float|int|null, discount?: float|int|null, notes?: string|null}  $data
     */
    public function collectCash(User $teacher, SubscriptionCharge $charge, array $data = []): Payment
    {
        $this->assertTeacherOwnsCharge($teacher, $charge);
        $charge->loadMissing(['subscription.plan', 'student']);
        $this->refreshChargeStatus($charge);
        $charge = $charge->fresh();

        if ($charge->status === ChargeStatus::Waived || $charge->status === ChargeStatus::Paid) {
            throw ValidationException::withMessages([
                'charge' => 'هذا الشهر مدفوع أو معفى بالفعل.',
            ]);
        }

        $remaining = $charge->remainingAmount();
        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'charge' => 'لا يوجد متبقي على هذا الشهر.',
            ]);
        }

        if (array_key_exists('discount', $data) && $data['discount'] !== null && $data['discount'] !== '') {
            $discount = max(0, (float) $data['discount']);
            if ($discount > (float) $charge->expected_amount) {
                throw ValidationException::withMessages([
                    'discount' => 'الخصم أكبر من قيمة الاشتراك.',
                ]);
            }
            $charge->update(['discount_amount' => $discount]);
            $charge = $charge->fresh();
            $remaining = $charge->remainingAmount();
        }

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
            ? (float) $data['amount']
            : $remaining;

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'أدخل مبلغًا أكبر من صفر.',
            ]);
        }

        if ($amount > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'المبلغ أكبر من المتبقي ('.$remaining.' ج.م).',
            ]);
        }

        return DB::transaction(function () use ($teacher, $charge, $amount, $data) {
            $payment = Payment::query()->create([
                'student_id' => $charge->student_id,
                'teacher_id' => $charge->teacher_id,
                'subscription_id' => $charge->subscription_id,
                'subscription_charge_id' => $charge->id,
                'branch_id' => $charge->student?->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::Cash,
                'provider' => 'manual',
                'amount' => $amount,
                'discount_amount' => 0,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $teacher->id,
                'notes' => $data['notes'] ?? ('تحصيل '.$charge->monthLabel()),
                'receipt_number' => $this->nextReceiptNumber($teacher),
            ]);

            $confirmed = $this->review->confirm($teacher, $payment);
            $this->refreshChargeStatus($charge->fresh());

            return $confirmed->fresh(['subscriptionCharge']);
        });
    }

    public function setDiscount(User $teacher, SubscriptionCharge $charge, float $discount): SubscriptionCharge
    {
        $this->assertTeacherOwnsCharge($teacher, $charge);

        if ($discount < 0 || $discount > (float) $charge->expected_amount) {
            throw ValidationException::withMessages([
                'discount' => 'قيمة الخصم غير صالحة.',
            ]);
        }

        $charge->update(['discount_amount' => $discount]);
        $this->refreshChargeStatus($charge->fresh());

        return $charge->fresh();
    }

    public function refreshChargeStatus(SubscriptionCharge $charge): SubscriptionCharge
    {
        if ($charge->status === ChargeStatus::Waived) {
            return $charge;
        }

        $paid = $charge->paidAmount();
        $net = $charge->netExpected();

        $status = match (true) {
            $paid <= 0 => ChargeStatus::Due,
            $paid + 0.001 >= $net => ChargeStatus::Paid,
            default => ChargeStatus::Partial,
        };

        if ($charge->status !== $status) {
            $charge->update(['status' => $status]);
        }

        return $charge->fresh();
    }

    public function owingTotalForMonth(User $teacher, Carbon|string|null $month = null): float
    {
        return (float) $this->listForTeacher($teacher, $month, owingOnly: true)
            ->sum(fn (SubscriptionCharge $c) => $c->remainingAmount());
    }

    public function owingCountForMonth(User $teacher, Carbon|string|null $month = null): int
    {
        return $this->listForTeacher($teacher, $month, owingOnly: true)->count();
    }

    public function nextReceiptNumber(User $teacher): string
    {
        $prefix = 'R-'.now()->format('Ym').'-'.$teacher->id.'-';

        $last = Payment::query()
            ->where('teacher_id', $teacher->id)
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertTeacher(User $teacher): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'غير مصرح بالتحصيل.',
            ]);
        }
    }

    private function assertTeacherOwnsCharge(User $teacher, SubscriptionCharge $charge): void
    {
        $this->assertTeacher($teacher);

        if ((int) $charge->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'charge' => 'المستحق خارج نطاقك.',
            ]);
        }
    }
}
