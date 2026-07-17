<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentRecordService
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly PaymentReviewService $review,
    ) {}

    /**
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    public function submitVodafoneProof(
        User $student,
        Subscription $subscription,
        array $data,
        ?UploadedFile $proof = null,
    ): Payment {
        $this->assertStudentOwnsSubscription($student, $subscription);

        if ($subscription->status !== SubscriptionStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك ليس بانتظار الدفع.',
            ]);
        }

        $subscription->loadMissing('plan', 'teacher');
        $proofPath = null;

        if ($proof) {
            $proofPath = $proof->store('payment-proofs', 'public');
        }

        return DB::transaction(function () use ($student, $subscription, $data, $proofPath) {
            $payment = Payment::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $subscription->teacher_id,
                'subscription_id' => $subscription->id,
                'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::VodafoneCash,
                'provider' => 'manual',
                'amount' => $data['amount'] ?? $subscription->plan->price,
                'external_reference' => $data['external_reference'] ?? null,
                'proof_path' => $proofPath,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $student->id,
                'notes' => $data['notes'] ?? null,
            ]);

            return $payment;
        });
    }

    /**
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    public function recordCash(User $recorder, User $student, Subscription $subscription, array $data): Payment
    {
        $this->assertCanRecordForStudent($recorder, $student, $subscription);
        $subscription->loadMissing('plan', 'teacher');

        if ($subscription->status !== SubscriptionStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك ليس بانتظار الدفع.',
            ]);
        }

        return DB::transaction(function () use ($recorder, $student, $subscription, $data) {
            $payment = Payment::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $subscription->teacher_id,
                'subscription_id' => $subscription->id,
                'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::Cash,
                'provider' => 'manual',
                'amount' => $data['amount'] ?? $subscription->plan->price,
                'external_reference' => $data['external_reference'] ?? null,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $recorder->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($recorder->hasAnyRole([UserRole::Teacher, UserRole::Admin])) {
                return $this->review->confirm($recorder, $payment);
            }

            return $payment;
        });
    }

    private function assertStudentOwnsSubscription(User $student, Subscription $subscription): void
    {
        if ($subscription->student_id !== $student->id) {
            throw ValidationException::withMessages([
                'subscription' => 'غير مصرح.',
            ]);
        }
    }

    private function assertCanRecordForStudent(User $recorder, User $student, Subscription $subscription): void
    {
        if ($recorder->hasRole(UserRole::Admin)) {
            return;
        }

        if ($recorder->hasRole(UserRole::Teacher)) {
            if ($subscription->teacher_id !== $recorder->id) {
                throw ValidationException::withMessages([
                    'subscription' => 'الاشتراك خارج نطاقك.',
                ]);
            }

            if (! $recorder->students()->where('users.id', $student->id)->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'الطالب غير مرتبط بك.',
                ]);
            }

            return;
        }

        if ($recorder->id === $student->id) {
            return;
        }

        throw ValidationException::withMessages([
            'payment' => 'غير مصرح بتسجيل الدفع.',
        ]);
    }
}
